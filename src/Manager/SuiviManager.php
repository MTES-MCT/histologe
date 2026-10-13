<?php

namespace App\Manager;

use App\Dto\Request\Signalement\InviteTiersRequest;
use App\Entity\Enum\DocumentType;
use App\Entity\Enum\SuiviCategory;
use App\Entity\Enum\SuiviDelayedType;
use App\Entity\File;
use App\Entity\Intervention;
use App\Entity\Partner;
use App\Entity\Signalement;
use App\Entity\Suivi;
use App\Entity\SuiviDelayed;
use App\Entity\SuiviFile;
use App\Entity\User;
use App\Event\SuiviCreatedEvent;
use App\Service\Signalement\Suivi\SuiviDescriptionHelper;
use App\Service\Signalement\Suivi\SuiviDescriptionVariant;
use App\Service\Signalement\Suivi\SuiviHelper;
use App\Utils\DateHelper;
use App\Utils\DictionaryProvider;
use App\Utils\Sanitizer;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Clock\ClockInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerInterface;

class SuiviManager
{
    private const string DATE_FORMAT = 'd/m/Y';

    public function __construct(
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly EntityManagerInterface $entityManager,
        private readonly Security $security,
        #[Autowire(service: 'html_sanitizer.sanitizer.app.message_sanitizer')]
        private readonly HtmlSanitizerInterface $htmlSanitizer,
        private readonly UserSignalementSubscriptionManager $userSignalementSubscriptionManager,
        private readonly UserManager $userManager,
        #[Autowire(env: 'EDITION_SUIVI_ENABLE')]
        private readonly bool $editionSuiviEnable,
        private readonly ClockInterface $clock,
        private readonly DictionaryProvider $dictionaryProvider,
        private readonly SuiviDescriptionHelper $suiviDescriptionHelper,
    ) {
    }

    /**
     * @param iterable<File> $files
     */
    public function createSuivi(
        Signalement $signalement,
        string $description,
        SuiviCategory $category,
        ?Partner $partner = null,
        ?User $user = null,
        bool $isVisibleForUsager = false,
        bool $isVisibleForBailleur = false,
        ?\DateTimeImmutable $createdAt = null,
        bool $sendMail = true,
        iterable $files = [],
        bool $createSubscription = true,
        bool &$subscriptionCreated = false,
    ): Suivi {
        // ticket #5252 Bloquer les emails aux usagers quand logement vacant
        if ($signalement->getIsLogementVacant() && $isVisibleForUsager) {
            $isVisibleForUsager = false;
        }
        $suivi = (new Suivi())
            ->setCreatedBy($user)
            ->setPartner($partner)
            ->setSignalement($signalement)
            ->setDescription($this->htmlSanitizer->sanitize($description))
            ->setType(SuiviCategory::getSuiviTypeForSuiviCategory($category))
            ->setIsVisibleForUsager($isVisibleForUsager)
            ->setIsVisibleForBailleur($isVisibleForBailleur)
            ->setSendMail($sendMail)
            ->setCategory($category);
        if (!empty($createdAt)) {
            $suivi->setCreatedAt($createdAt);
        } else {
            $suivi->setCreatedAt($this->clock->now());
        }
        if (SuiviCategory::MESSAGE_PARTNER === $suivi->getCategory() && $this->editionSuiviEnable) {
            $suivi->setWaitingNotification(true);
        }
        foreach ($files as $file) {
            $suiviFile = (new SuiviFile())->setFile($file)->setSuivi($suivi)->setTitle($file->getTitle());
            $this->entityManager->persist($suiviFile);
            $suivi->addSuiviFile($suiviFile);
        }
        // abonnement au signalement si le suivi est crée par un agent non abonné
        if ($createSubscription && $this->userSignalementSubscriptionManager->doesUserNeedSubscription($user, $suivi->getCategory(), $signalement)) {
            $this->userSignalementSubscriptionManager->createOrGet(
                userToSubscribe: $user,
                signalement: $signalement,
                createdBy: $user,
                subscriptionCreated: $subscriptionCreated
            );
        }
        // dénormalisation des champs vers le signalement
        $signalement->setLastSuiviAt($suivi->getCreatedAt());
        $signalement->setLastSuiviBy(SuiviHelper::getLastLabelFromSuivi($suivi));
        $signalement->setLastSuiviIsVisibleForUsager($suivi->getIsVisibleForUsager());

        $this->entityManager->persist($suivi);
        $this->eventDispatcher->dispatch(new SuiviCreatedEvent($suivi), SuiviCreatedEvent::NAME);

        return $suivi;
    }

    public function addInviteSuiviFromBo(
        Signalement $signalement,
        InviteTiersRequest $inviteTiersRequest,
    ): bool {
        $subscriptionCreated = false;
        /** @var User $user */
        $user = $this->security->getUser();
        $description = $this->suiviDescriptionHelper->buildDescriptionForCreation(
            SuiviCategory::SIGNALEMENT_EDITED_BO,
            [
                'nom' => $inviteTiersRequest->getNom(),
                'prenom' => $inviteTiersRequest->getPrenom(),
                'email' => $inviteTiersRequest->getMail(),
                'telephone' => $inviteTiersRequest->getTelephone(),
            ],
            SuiviDescriptionVariant::INVITATION_TIERS,
        );

        $this->createSuivi(
            signalement: $signalement,
            description: $description,
            category: SuiviCategory::SIGNALEMENT_EDITED_BO,
            partner: $user->getPartnerInTerritoryOrFirstOne($signalement->getAddress()->getTerritory()),
            user: $user,
            isVisibleForUsager: true,
            subscriptionCreated: $subscriptionCreated
        );

        return $subscriptionCreated;
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
            category: SuiviCategory::SIGNALEMENT_EDITED_FO,
            user: $this->userManager->getSystemUser(),
            isVisibleForUsager: true,
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
            category: SuiviCategory::SIGNALEMENT_EDITED_FO,
            user: $this->userManager->getSystemUser(),
            isVisibleForUsager: true,
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
        $isVisibleForUsager = false;
        $context = null;

        if (
            \array_key_exists($documentType?->value, DocumentType::getOrderedProcedureList())
            && null === $intervention
        ) {
            $context = 'procedure';
        }

        if (\array_key_exists($documentType?->value, DocumentType::getOrderedSituationList())) {
            $isVisibleForUsager = true;
            $context = 'situation';
        }

        if (DocumentType::PROCEDURE_RAPPORT_DE_VISITE === $documentType && null !== $intervention) {
            $isVisibleForUsager = true;
            $partner = $user->getPartnerInTerritoryOrFirstOne($signalement->getAddress()->getTerritory());
            $context = 'rapport_visite';
        }

        if (DocumentType::PHOTO_VISITE === $documentType && null !== $intervention) {
            $isVisibleForUsager = true;
            $partner = $user->getPartnerInTerritoryOrFirstOne($signalement->getAddress()->getTerritory());
            $context = 'photo_visite';
        }

        $description = $this->suiviDescriptionHelper->buildDescriptionForCreation(SuiviCategory::NEW_DOCUMENT, [
            'context' => $context,
            'nbDocs' => $nbDocs,
            'partnerName' => $partner?->getNom(),
            'dateVisite' => $intervention?->getScheduledAt()?->format('d/m/Y'),
        ]);

        return $this->createSuivi(
            signalement: $signalement,
            description: $description,
            category: SuiviCategory::NEW_DOCUMENT,
            partner: $partner,
            user: $user,
            isVisibleForUsager: $isVisibleForUsager,
            isVisibleForBailleur: false,
            files: $files,
            subscriptionCreated: $subscriptionCreated
        );
    }

    /**
     * @param array<string, mixed> $params
     */
    public static function buildDescriptionClotureSignalement(array $params): string
    {
        $motifSuivi = Sanitizer::sanitize($params['motif_suivi']);

        $motifCloture = '<strong>'.$params['motif_cloture']->label().'</strong>';
        if (!empty($params['travaux_mise_en_conformite'])) {
            $motifCloture .= '<br><strong>'.SuiviDescriptionHelper::DESCRIPTION_TRAVAUX_MISE_EN_CONFORMITE.'</strong>'.$params['travaux_mise_en_conformite']->label();
        }

        $description = \sprintf(SuiviDescriptionHelper::DESCRIPTION_MOTIF_CLOTURE_PARTNER, $params['subject']);
        $description .= '<br>';
        $description .= $motifCloture;
        $description .= '<br>';
        if (isset($params['procedures']) && is_iterable($params['procedures'])) {
            $procedureLabels = [];
            foreach ($params['procedures'] as $procedure) {
                $procedureLabels[] = $procedure->getProcedureType()->label();
            }
            if ([] !== $procedureLabels) {
                $description .= '<strong>Procédures engagées : </strong>';
                $description .= implode(' / ', $procedureLabels);
                $description .= '<br>';
            }
        }
        $description .= '<strong>Desc. : </strong>'.$motifSuivi;

        return $description;
    }

    /**
     * @param array<string, mixed> $params
     */
    public static function buildDescriptionAnswerAffectation(array $params): string
    {
        $motifRejected = !empty($params['motifRefus']) ? $params['motifRefus']->label() : 'Non précisé';
        $commentaire = Sanitizer::sanitize($params['suivi']);

        return 'Le signalement a été refusé avec le motif suivant : '.$motifRejected.'.<br>Plus précisément :<br>'.$commentaire;
    }

    /**
     * @param array<SuiviDelayed> $suivisDelayed
     */
    public function createSuiviFromSuiviDelayedList(array $suivisDelayed): void
    {
        $signalement = $suivisDelayed[0]->getSignalement();
        $user = $suivisDelayed[0]->getUser();
        $category = $suivisDelayed[0]->getSuiviCategory();
        $changesGroupedByType = [];
        $changesCountedByType = [];
        $filesToAttach = [];
        foreach ($suivisDelayed as $suiviDelayed) {
            $type = $suiviDelayed->getSuiviDelayedType()->value;
            if ($suiviDelayed->getChanges()) {
                if ($type === SuiviDelayedType::BO_EDIT_OCCUPATION_LOGEMENT->value) {
                    $changesCountedByType[$type] = $suiviDelayed->getChanges()['description'];
                } else {
                    $changesGroupedByType[$type] = array_merge($changesGroupedByType[$type] ?? [], $suiviDelayed->getChanges());
                }
            } elseif (count($suiviDelayed->getFiles())) {
                $filesToAttach = array_merge($filesToAttach, $suiviDelayed->getFiles()->toArray());
            } else {
                if (!isset($changesCountedByType[$type])) {
                    $changesCountedByType[$type] = 1;
                } else {
                    ++$changesCountedByType[$type];
                }
            }
        }

        $groupedChanges = [];
        foreach ($changesGroupedByType as $type => $changes) {
            $groupedChanges[] = [
                'label' => SuiviDelayedType::from($type)->label(),
                // valeurs déjà formatées en HTML (échappées) par formatValue()
                'changes' => array_map(fn (mixed $value): string => $this->formatValue($value), $changes),
            ];
        }

        $otherChanges = [];
        foreach ($changesCountedByType as $type => $value) {
            $suiviDelayedType = SuiviDelayedType::from($type);
            $otherChanges[] = match ($suiviDelayedType) {
                SuiviDelayedType::FO_FILE_DELETED => $value.' '.$suiviDelayedType->label(),
                SuiviDelayedType::BO_EDIT_OCCUPATION_LOGEMENT => (string) $value,
                default => $suiviDelayedType->label(),
            };
        }

        $description = $this->suiviDescriptionHelper->buildDescriptionForCreation($category, [
            'nomComplet' => $user->getNomComplet(true),
            'groupedChanges' => $groupedChanges,
            'otherChanges' => $otherChanges,
            'showOtherChangesTitle' => SuiviCategory::SIGNALEMENT_EDITED_BO !== $category,
        ]);

        $partner = null;
        $isVisibleForUsager = true;
        if (SuiviCategory::SIGNALEMENT_EDITED_BO === $category) {
            $partner = $user->getPartnerInTerritoryOrFirstOne($signalement->getAddress()->getTerritory());
            $isVisibleForUsager = false;
        }

        $this->createSuivi(
            signalement: $signalement,
            description: $description,
            category: $category,
            partner: $partner,
            user: $user,
            isVisibleForUsager: $isVisibleForUsager,
            files: $filesToAttach
        );
    }

    private function formatValue(mixed $value): string
    {
        // Si c'est un champ de type DateTimeInterface, on formate la date pour que ce soit plus lisible dans le suivi
        if ($value instanceof \DateTimeInterface) {
            $value = $value->format('d/m/Y');
        } else {
            $dateFormatted = empty($value) ? false : DateHelper::formatDateString($value, 'Y-m-d', self::DATE_FORMAT);
            if (false !== $dateFormatted) {
                return $dateFormatted;
            }
            if (null !== $value) {
                $value = $this->dictionaryProvider->translate($value, 'suivi');
                $value = nl2br(htmlentities($value));
            }
        }
        if (null === $value || '' === $value) {
            $value = '<i>(valeur supprimée)</i>';
        }

        return $value;
    }
}
