<?php

namespace App\Manager;

use App\Entity\Enum\DocumentType;
use App\Entity\Enum\SignalementStatus;
use App\Entity\Enum\SuiviCategory;
use App\Entity\File;
use App\Entity\Intervention;
use App\Entity\Partner;
use App\Entity\Signalement;
use App\Entity\Suivi;
use App\Entity\SuiviFile;
use App\Entity\User;
use App\Event\SuiviCreatedEvent;
use App\EventListener\SignalementUpdatedListener;
use App\Service\Sanitizer;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerInterface;

class SuiviManager extends Manager
{
    public function __construct(
        protected ManagerRegistry $managerRegistry,
        private readonly SignalementUpdatedListener $signalementUpdatedListener,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly Security $security,
        #[Autowire(service: 'html_sanitizer.sanitizer.app.message_sanitizer')]
        private readonly HtmlSanitizerInterface $htmlSanitizer,
        private readonly UserSignalementSubscriptionManager $userSignalementSubscriptionManager,
        #[Autowire(env: 'FEATURE_EDITION_SUIVI')]
        private readonly bool $featureEditionSuivi,
        string $entityName = Suivi::class,
    ) {
        parent::__construct($managerRegistry, $entityName);
    }

    /**
     * @param iterable<File> $files
     */
    public function createSuivi(
        Signalement $signalement,
        string $description,
        int $type,
        SuiviCategory $category,
        ?Partner $partner = null,
        ?User $user = null,
        bool $isPublic = false,
        ?\DateTimeImmutable $createdAt = null,
        ?string $context = null,
        bool $sendMail = true,
        iterable $files = [],
        bool $flush = true,
        bool &$subscriptionCreated = false,
    ): Suivi {
        $suivi = (new Suivi())
            ->setCreatedBy($user)
            ->setPartner($partner)
            ->setSignalement($signalement)
            ->setDescription($this->htmlSanitizer->sanitize($description))
            ->setType($type)
            ->setIsPublic($isPublic)
            ->setContext($context)
            ->setSendMail($sendMail)
            ->setCategory($category);
        if (!empty($createdAt)) {
            $suivi->setCreatedAt($createdAt);
        }
        if (SuiviCategory::MESSAGE_PARTNER === $suivi->getCategory() && $this->featureEditionSuivi) {
            $suivi->setWaitingNotification(true);
        }
        foreach ($files as $file) {
            $suiviFile = (new SuiviFile())->setFile($file)->setSuivi($suivi)->setTitle($file->getTitle());
            $this->persist($suiviFile);
            $suivi->addSuiviFile($suiviFile);
        }
        // abonnement au signalement si le suivi est crée par un agent non abonné
        if ($this->doesUserNeedSubscription($user, $suivi)) {
            $this->userSignalementSubscriptionManager->createOrGet(
                userToSubscribe: $user,
                signalement: $signalement,
                createdBy: $user,
                subscriptionCreated: $subscriptionCreated
            );
        }
        if ($flush) {
            $this->save($suivi);
        } else {
            $this->persist($suivi);
        }
        $this->eventDispatcher->dispatch(new SuiviCreatedEvent($suivi), SuiviCreatedEvent::NAME);

        return $suivi;
    }

    private function doesUserNeedSubscription(
        ?User $user,
        Suivi $suivi,
    ): bool {
        if (!$user) {
            return false;
        }
        if ($user->isUsager() || $user->isApiUser() || $user->isSuperAdmin()) {
            return false;
        }
        if (in_array($suivi->getCategory(), [
            SuiviCategory::AFFECTATION_IS_ACCEPTED,
            SuiviCategory::AFFECTATION_IS_REFUSED,
            SuiviCategory::MESSAGE_USAGER,
            SuiviCategory::MESSAGE_USAGER_POST_CLOTURE,
            SuiviCategory::DOCUMENT_DELETED_BY_USAGER,
            SuiviCategory::DEMANDE_ABANDON_PROCEDURE,
            SuiviCategory::DEMANDE_POURSUITE_PROCEDURE,
            SuiviCategory::SIGNALEMENT_STATUS_IS_SYNCHRO,
        ])) {
            return false;
        }
        if (SignalementStatus::DRAFT === $suivi->getSignalement()->getStatut()) {
            return false;
        }

        return true;
    }

    public function addSuiviIfNeeded(
        Signalement $signalement,
        string $description,
    ): bool {
        $subscriptionCreated = false;
        if ($this->signalementUpdatedListener->updateOccurred()) {
            /** @var User $user */
            $user = $this->security->getUser();
            $this->createSuivi(
                signalement: $signalement,
                description: $description.$user->getNomComplet(),
                type: Suivi::TYPE_AUTO,
                category: SuiviCategory::SIGNALEMENT_EDITED_BO,
                partner: $user->getPartnerInTerritoryOrFirstOne($signalement->getTerritory()),
                user: $user,
                subscriptionCreated: $subscriptionCreated
            );
        }

        return $subscriptionCreated;
    }

    /**
     * @param array<int, File> $files
     */
    public function createInstanceForFilesSignalement(
        User $user,
        Signalement $signalement,
        array $files,
        ?Partner $partner = null,
        bool &$subscriptionCreated = false,
    ): Suivi {
        $nbDocs = count($files);
        /** @var ?DocumentType $documentType */
        $documentType = null;

        /** @var ?Intervention $intervention */
        $intervention = null;
        /** @var File $file */
        foreach ($files as $file) {
            $documentType = $file->getDocumentType();
            $intervention = $file->getIntervention();
        }
        $description = '';
        $isVisibleUsager = false;

        if (
            \array_key_exists($documentType?->value, DocumentType::getOrderedProcedureList())
            && null === $intervention
        ) {
            if ($nbDocs > 0) {
                $description .= $nbDocs;
                $description .= $nbDocs > 1 ? ' documents liés à la procédure ont été ajoutés' : ' document lié à la procédure a été ajouté';
                $description .= ' au signalement.';
            }
        }

        if (\array_key_exists($documentType?->value, DocumentType::getOrderedSituationList())) {
            $isVisibleUsager = true;
            if ($nbDocs > 0) {
                $description .= $nbDocs;
                $description .= $nbDocs > 1 ? ' documents ' : ' document ';
                $description .= 'sur la situation usager';
                $description .= $nbDocs > 1 ? ' ont été ajoutés au signalement : ' : ' a été ajouté au signalement : ';
            }
        }

        if (DocumentType::PROCEDURE_RAPPORT_DE_VISITE === $documentType && null !== $intervention) {
            $isVisibleUsager = true;
            $partner = $user->getPartnerInTerritoryOrFirstOne($signalement->getTerritory());
            if ($nbDocs > 0) {
                $description .= \sprintf(
                    '%s a ajouté %s %s de la visite du %s :',
                    $partner->getNom(),
                    $nbDocs,
                    $nbDocs > 1 ? ' rapports de visite' : ' rapport de visite',
                    $intervention->getScheduledAt()->format('d/m/Y')
                );
            }
        }

        if (DocumentType::PHOTO_VISITE === $documentType && null !== $intervention) {
            $isVisibleUsager = true;
            $partner = $user->getPartnerInTerritoryOrFirstOne($signalement->getTerritory());
            if ($nbDocs > 0) {
                $description .= \sprintf(
                    '%s a ajouté %s %s de la visite du %s :',
                    $partner->getNom(),
                    $nbDocs,
                    $nbDocs > 1 ? ' photos' : ' photo',
                    $intervention->getScheduledAt()->format('d/m/Y')
                );
            }
        }

        return $this->createSuivi(
            signalement: $signalement,
            description: $description,
            type: Suivi::TYPE_AUTO,
            category: SuiviCategory::NEW_DOCUMENT,
            partner: $partner,
            user: $user,
            isPublic: $isVisibleUsager,
            files: $files,
            flush: false,
            subscriptionCreated: $subscriptionCreated
        );
    }

    /**
     * @param array<string, mixed> $params
     */
    public static function buildDescriptionClotureSignalement(array $params): string
    {
        $motifSuivi = Sanitizer::sanitize($params['motif_suivi']);

        return \sprintf(
            Suivi::DESCRIPTION_MOTIF_CLOTURE_PARTNER.' %s avec le motif suivant <br><strong>%s</strong><br><strong>Desc. : </strong>%s',
            $params['subject'],
            $params['motif_cloture']->label(),
            $motifSuivi
        );
    }

    /**
     * @param array<string, mixed> $params
     */
    public static function buildDescriptionAnswerAffectation(array $params): string
    {
        $description = '';
        if (isset($params['accept'])) {
            $description = 'Le signalement a été accepté';
        } elseif (isset($params['suivi'])) {
            $motifRejected = !empty($params['motifRefus']) ? $params['motifRefus']->label() : 'Non précisé';
            $commentaire = Sanitizer::sanitize($params['suivi']);
            $description = 'Le signalement a été refusé avec le motif suivant : '.$motifRejected.'.<br>Plus précisément :<br>'.$commentaire;
        }

        return $description;
    }
}
