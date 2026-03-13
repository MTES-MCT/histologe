<?php

namespace App\Manager;

use App\Dto\Request\Signalement\InviteTiersRequest;
use App\Entity\Enum\DocumentType;
use App\Entity\Enum\SignalementStatus;
use App\Entity\Enum\SuiviCategory;
use App\Entity\File;
use App\Entity\Intervention;
use App\Entity\Partner;
use App\Entity\Signalement;
use App\Entity\Suivi;
use App\Entity\SuiviFile;
use App\Entity\TiersInvitation;
use App\Entity\User;
use App\Event\SuiviCreatedEvent;
use App\Security\User\SignalementUser;
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
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly Security $security,
        #[Autowire(service: 'html_sanitizer.sanitizer.app.message_sanitizer')]
        private readonly HtmlSanitizerInterface $htmlSanitizer,
        private readonly UserSignalementSubscriptionManager $userSignalementSubscriptionManager,
        private readonly UserManager $userManager,
        #[Autowire(env: 'EDITION_SUIVI_ENABLE')]
        private readonly bool $editionSuiviEnable,
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
        bool $createSubscription = true,
        bool &$subscriptionCreated = false,
    ): Suivi {
        // ticket #5252 Bloquer les emails aux usagers quand logement vacant
        if ($signalement->getIsLogementVacant() && $isPublic) {
            $isPublic = false;
        }
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
        if (SuiviCategory::MESSAGE_PARTNER === $suivi->getCategory() && $this->editionSuiviEnable) {
            $suivi->setWaitingNotification(true);
        }
        foreach ($files as $file) {
            $suiviFile = (new SuiviFile())->setFile($file)->setSuivi($suivi)->setTitle($file->getTitle());
            $this->persist($suiviFile);
            $suivi->addSuiviFile($suiviFile);
        }
        // abonnement au signalement si le suivi est crée par un agent non abonné
        if ($createSubscription && $this->doesUserNeedSubscription($user, $suivi)) {
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
            SuiviCategory::SIGNALEMENT_EDITED_FO,
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
        if ($signalement->isUpdateOccurred()) {
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

    public function addInviteSuiviFromBo(
        Signalement $signalement,
        InviteTiersRequest $inviteTiersRequest,
    ): bool {
        $subscriptionCreated = false;
        /** @var User $user */
        $user = $this->security->getUser();
        $description = 'Une invitation a été envoyée à un tiers pour suivre le signalement :';
        $description .= $this->buildTiersInfoHtml(
            $inviteTiersRequest->getNom(),
            $inviteTiersRequest->getPrenom(),
            $inviteTiersRequest->getMail(),
            $inviteTiersRequest->getTelephone(),
        );

        $this->createSuivi(
            signalement: $signalement,
            description: $description,
            type: Suivi::TYPE_AUTO,
            category: SuiviCategory::SIGNALEMENT_EDITED_BO,
            partner: $user->getPartnerInTerritoryOrFirstOne($signalement->getTerritory()),
            user: $user,
            isPublic: true,
            subscriptionCreated: $subscriptionCreated
        );

        return $subscriptionCreated;
    }

    public function addInviteSuiviFromFo(TiersInvitation $tiersInvitation): void
    {
        $description = 'L\'usager a envoyé une invitation à un tiers pour suivre le signalement :';
        $description .= $this->buildTiersInfoHtml(
            $tiersInvitation->getLastname(),
            $tiersInvitation->getFirstname(),
            $tiersInvitation->getEmail(),
            $tiersInvitation->getTelephone(),
        );

        $this->createSuivi(
            signalement: $tiersInvitation->getSignalement(),
            description: $description,
            type: Suivi::TYPE_AUTO,
            category: SuiviCategory::SIGNALEMENT_EDITED_FO,
            user: $this->userManager->getSystemUser(),
            isPublic: false,
        );
    }

    public function addAccepteInvitationSuivi(Signalement $signalement): void
    {
        $description = 'Un tiers a accepté une invitation pour suivre le signalement :';
        $description .= $this->buildTiersInfoHtml(
            $signalement->getNomDeclarant(),
            $signalement->getPrenomDeclarant(),
            $signalement->getMailDeclarant(),
            $signalement->getTelDeclarantDecoded(),
        );

        $this->createSuivi(
            signalement: $signalement,
            description: $description,
            type: Suivi::TYPE_AUTO,
            category: SuiviCategory::SIGNALEMENT_EDITED_FO,
            user: $this->userManager->getSystemUser(),
            isPublic: true,
        );
    }

    public function addRefuseInvitationSuivi(Signalement $signalement): void
    {
        $description = 'Le tiers a refusé l\'invitation pour suivre le signalement :';
        $description .= $this->buildTiersInfoHtml(
            $signalement->getNomDeclarant(),
            $signalement->getPrenomDeclarant(),
            $signalement->getMailDeclarant(),
            $signalement->getTelDeclarantDecoded(),
        );

        $this->createSuivi(
            signalement: $signalement,
            description: $description,
            type: Suivi::TYPE_AUTO,
            category: SuiviCategory::SIGNALEMENT_EDITED_FO,
            user: $this->userManager->getSystemUser(),
            isPublic: true,
        );
    }

    private function buildTiersInfoHtml(
        ?string $nom,
        ?string $prenom,
        ?string $email,
        ?string $telephone,
    ): string {
        $description = '<br><ul>';
        $description .= $nom ? '<li>Nom : '.$nom.'</li>' : '';
        $description .= $prenom ? '<li>Prénom : '.$prenom.'</li>' : '';
        $description .= $email ? '<li>E-mail : '.$email.'</li>' : '';
        $description .= $telephone ? '<li>Téléphone : '.$telephone.'</li>' : '';
        $description .= '</ul>';

        return $description;
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

    public function createSuiviFromEditUsager(
        Signalement $signalement,
        SignalementUser $signalementUser,
    ): void {
        $changes = $signalement->getChanges();

        if ([] === $changes) {
            return;
        }

        /** @var User $user */
        $user = $signalementUser->getUser();

        /** @var array{label:string, fieldChanges:array} $sectionChanges */
        // Un seul formulaire est soumis à la fois,
        // donc un seul bloc de changements est attendu.
        $sectionChanges = current($changes);

        if (UserManager::OCCUPANT === $signalementUser->getType()) {
            $nomComplet = $signalement->getNomOccupantComplet(true);
        } else {
            $nomComplet = $signalement->getNomDeclarantComplet(true);
        }

        $description = sprintf(
            '%s ont été modifiées par %s.',
            $sectionChanges['label'],
            htmlentities($nomComplet),
        );
        $description .= '<ul>';

        foreach ($sectionChanges['fieldChanges'] as $change) {
            $new = $change['new'];
            // Si c'est un champ de type DateTimeImmutable, on formate la date pour que ce soit plus lisible dans le suivi
            if ($new instanceof \DateTimeImmutable) {
                $new = $new->format('d/m/Y');
            } elseif (null === $new || '' === $new) {
                $new = '<i>(valeur supprimée)</i>';
            } else {
                $new = nl2br(htmlentities($new));
            }
            $description .= sprintf(
                '<li>%s : %s</li>',
                $change['label'],
                $new,
            );
        }

        $description .= '</ul>';
        $this->createSuivi(
            signalement: $signalement,
            description: $description,
            type: Suivi::TYPE_USAGER,
            category: SuiviCategory::SIGNALEMENT_EDITED_FO,
            user: $user,
            isPublic: true,
        );
    }
}
