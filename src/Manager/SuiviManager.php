<?php

namespace App\Manager;

use App\Entity\Enum\DocumentType;
use App\Entity\Enum\SignalementStatus;
use App\Entity\Enum\SuiviCategory;
use App\Entity\File;
use App\Entity\Intervention;
use App\Entity\Signalement;
use App\Entity\Suivi;
use App\Entity\SuiviFile;
use App\Entity\User;
use App\Entity\UserSignalementSubscription;
use App\Event\SuiviCreatedEvent;
use App\EventListener\SignalementUpdatedListener;
use App\Repository\UserSignalementSubscriptionRepository;
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
        private readonly UserSignalementSubscriptionRepository $userSignalementSubscriptionRepository,
        #[Autowire(env: 'FEATURE_NEW_DASHBOARD')]
        private readonly bool $featureNewDashboard,
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
        bool $isPublic = false,
        ?User $user = null,
        ?\DateTimeImmutable $createdAt = null,
        ?string $context = null,
        bool $sendMail = true,
        iterable $files = [],
        bool $flush = true,
        bool &$subscriptionCreated = false,
    ): Suivi {
        $suivi = (new Suivi())
            ->setCreatedBy($user)
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
        foreach ($files as $file) {
            $suiviFile = (new SuiviFile())->setFile($file)->setSuivi($suivi)->setTitle($file->getTitle());
            $this->persist($suiviFile);
            $suivi->addSuiviFile($suiviFile);
        }
        // abonnement au signalement si le suivi est crée par un agent non abonné
        if ($this->doesUserNeedSubscription($user, $suivi)) {
            $subscription = $this->userSignalementSubscriptionRepository->findOneBy(['user' => $user, 'signalement' => $signalement]);
            if (!$subscription) {
                $subscription = new UserSignalementSubscription();
                $subscription->setUser($user)
                            ->setSignalement($signalement)
                            ->setCreatedBy($user);
                $this->persist($subscription);
                $subscriptionCreated = true;
                echo 'Subscription created for: '.$user->getEmail().\PHP_EOL;
            }
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
        echo 'doesUserNeedSubscription : '.$user->getEmail().\PHP_EOL;
        echo $this->featureNewDashboard ? 'Feature new dashboard is enabled.'.\PHP_EOL : 'Feature new dashboard is disabled.'.\PHP_EOL;
        if (!$this->featureNewDashboard) {
            return false;
        }
        if (!$user) {
            return false;
        }
        echo ' isUsager: '.$user->isUsager().' isApiUser: '.$user->isApiUser().\PHP_EOL;
        if ($user->isUsager() || $user->isApiUser()) {
            return false;
        }
        if (in_array($suivi->getCategory(), [
            SuiviCategory::AFFECTATION_IS_ACCEPTED,
            SuiviCategory::AFFECTATION_IS_REFUSED,
            SuiviCategory::MESSAGE_USAGER,
            SuiviCategory::DOCUMENT_DELETED_BY_USAGER,
            SuiviCategory::DEMANDE_ABANDON_PROCEDURE,
            SuiviCategory::DEMANDE_POURSUITE_PROCEDURE,
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
                user: $user,
                subscriptionCreated: $subscriptionCreated
            );
        }

        return $subscriptionCreated;
    }

    /**
     * @param array<int, File> $files
     */
    public function createInstanceForFilesSignalement(User $user, Signalement $signalement, array $files, bool &$subscriptionCreated = false): Suivi
    {
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
            isPublic: $isVisibleUsager,
            user: $user,
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
