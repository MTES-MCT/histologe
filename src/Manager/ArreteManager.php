<?php

namespace App\Manager;

use App\Dto\Api\Request\ArreteRequest;
use App\Entity\Affectation;
use App\Entity\Arrete;
use App\Entity\Enum\ArreteCreationSource;
use App\Entity\Enum\ArreteType;
use App\Entity\Enum\NotificationType;
use App\Entity\Enum\ProcedureType;
use App\Entity\Enum\SuiviCategory;
use App\Entity\Signalement;
use App\Factory\ArreteFactory;
use App\Factory\NotificationFactory;
use App\Repository\ArreteRepository;
use App\Repository\UserRepository;
use App\Service\Arrete\ArreteDescriptionGenerator;
use App\Service\Interconnection\Esabora\AbstractEsaboraService;
use App\Service\Interconnection\Esabora\Response\Model\DossierArreteSISH;
use App\Service\Mailer\NotificationMail;
use App\Service\Mailer\NotificationMailerRegistry;
use App\Service\Mailer\NotificationMailerType;
use App\Service\Signalement\Qualification\SignalementQualificationUpdater;
use Doctrine\ORM\EntityManagerInterface;

class ArreteManager
{
    public function __construct(
        private readonly ArreteRepository $arreteRepository,
        private readonly UserRepository $userRepository,
        private readonly UserManager $userManager,
        private readonly SuiviManager $suiviManager,
        private readonly EntityManagerInterface $entityManager,
        private readonly NotificationFactory $notificationFactory,
        private readonly ArreteFactory $arreteFactory,
        private readonly NotificationMailerRegistry $notificationMailerRegistry,
        private readonly SignalementQualificationUpdater $signalementQualificationUpdater,
    ) {
    }

    public function arreteAlreadyExists(ArreteRequest $arreteRequest, Signalement $signalement): bool
    {
        return null !== $this->arreteRepository->findOneBy([
            'arreteCreationSource' => ArreteCreationSource::API,
            'signalement' => $signalement,
            'dateArrete' => new \DateTimeImmutable($arreteRequest->date),
            'arreteType' => ArreteType::fromLabel($arreteRequest->type),
        ]);
    }

    public function createArreteFromApiRequest(ArreteRequest $arreteRequest, Affectation $affectation): Arrete
    {
        $signalement = $affectation->getSignalement();
        $additionalInformation = [
            'numero_dossier' => $arreteRequest->numeroDossier,
            'arrete_numero' => $arreteRequest->numero,
            'arrete_mainlevee_numero' => $arreteRequest->mainLeveeNumero,
            'arrete_modificatif_date' => null,
            'arrete_modificatif_numero' => null,
        ];

        $arrete = $this->arreteFactory->createInstance(
            dateArrete: new \DateTimeImmutable($arreteRequest->date),
            arreteType: ArreteType::fromLabel($arreteRequest->type),
            address: $signalement->getAddress(),
            dateMainLevee: $arreteRequest->mainLeveeDate ? new \DateTimeImmutable($arreteRequest->mainLeveeDate) : null,
            additionalInformation: $additionalInformation,
            signalement: $signalement,
            arreteCreationSource: ArreteCreationSource::API,
        );
        $this->entityManager->persist($arrete);
        $this->signalementQualificationUpdater->updateQualificationFromVisiteProcedureList($signalement, [ProcedureType::INSALUBRITE]);

        $this->createSuiviAndNotifyArrete($arrete, $affectation);

        return $arrete;
    }

    public function createArreteFromDossierArreteSISH(DossierArreteSISH $dossierArreteSISH, Affectation $affectation): void
    {
        $signalement = $affectation->getSignalement();
        $hasArreteModificatif = null !== $dossierArreteSISH->getArreteModificatifDate();
        $additionalInformation = [
            'numero_dossier' => $dossierArreteSISH->getDossNum(),
            'arrete_numero' => $dossierArreteSISH->getArreteNumero(),
            'arrete_mainlevee_numero' => $dossierArreteSISH->getArreteMLNumero(),
            'arrete_modificatif_date' => $hasArreteModificatif ? $dossierArreteSISH->getArreteModificatifDate() : null,
            'arrete_modificatif_numero' => $hasArreteModificatif ? $dossierArreteSISH->getArreteModificatifNumero() : null,
        ];

        $arrete = $this->arreteFactory->createInstance(
            dateArrete: $this->esaboraDateToDateTimeImmutable($dossierArreteSISH->getArreteDate()),
            arreteType: ArreteType::fromEsaboraLabel($dossierArreteSISH->getArreteType()),
            address: $signalement->getAddress(),
            dateMainLevee: $dossierArreteSISH->getArreteMLDate() ? $this->esaboraDateToDateTimeImmutable($dossierArreteSISH->getArreteMLDate()) : null,
            additionalInformation: $additionalInformation,
            signalement: $signalement,
            arreteCreationSource: ArreteCreationSource::ESABORA,
            providerId: $dossierArreteSISH->getArreteId(),
        );
        $this->entityManager->persist($arrete);
        $this->signalementQualificationUpdater->updateQualificationFromVisiteProcedureList($signalement, [ProcedureType::INSALUBRITE]);

        $this->createSuiviAndNotifyArrete($arrete, $affectation);
    }

    /**
     * @throws \Exception
     */
    public function updateFromDossierArreteSISH(
        Arrete $arrete,
        DossierArreteSISH $dossierArreteSISH,
        Affectation $affectation,
    ): bool {
        $existingAdditionalInformation = $arrete->getAdditionalInformation();
        $changedInformations = [];
        $hadMainLevee = (bool) $arrete->getDateMainLevee();
        $hadModificatif = !empty($existingAdditionalInformation['arrete_modificatif_date']);
        // date
        if ($dossierArreteSISH->getArreteDate() && $arrete->getDateArrete()->format(AbstractEsaboraService::FORMAT_DATE) !== $dossierArreteSISH->getArreteDate()) {
            $changedInformations['arrete_date'] = $dossierArreteSISH->getArreteDate();
            $arrete->setDateArrete($this->esaboraDateToDateTimeImmutable($dossierArreteSISH->getArreteDate()));
        }
        // numero (peut passer de renseigné a null)
        if ($existingAdditionalInformation['arrete_numero'] !== $dossierArreteSISH->getArreteNumero()) {
            $changedInformations['arrete_numero'] = $dossierArreteSISH->getArreteNumero();
            $existingAdditionalInformation['arrete_numero'] = $dossierArreteSISH->getArreteNumero();
        }
        // numero de dossier (n'était pas stocké avant le ticket #6288)
        // on enregistre un changement uniquement si le numéro de dossier existait déjà et qu'il diffère
        if ($dossierArreteSISH->getDossNum() && isset($existingAdditionalInformation['numero_dossier']) && $existingAdditionalInformation['numero_dossier'] !== $dossierArreteSISH->getDossNum()) {
            $changedInformations['numero_dossier'] = $dossierArreteSISH->getDossNum();
            $existingAdditionalInformation['numero_dossier'] = $dossierArreteSISH->getDossNum();
        }
        // type
        if ($dossierArreteSISH->getArreteType() && $arrete->getArreteType() !== ArreteType::fromEsaboraLabel($dossierArreteSISH->getArreteType())) {
            $changedInformations['arrete_type'] = $dossierArreteSISH->getArreteType();
            $arrete->setArreteType(ArreteType::fromEsaboraLabel($dossierArreteSISH->getArreteType()));
        }
        // main levée (le numéro peut passer de renseigné a null si une date est renseignée)
        if ($dossierArreteSISH->getArreteMLDate() && $existingAdditionalInformation['arrete_mainlevee_numero'] !== $dossierArreteSISH->getArreteMLNumero()) {
            $changedInformations['arrete_mainlevee_numero'] = $dossierArreteSISH->getArreteMLNumero();
            $existingAdditionalInformation['arrete_mainlevee_numero'] = $dossierArreteSISH->getArreteMLNumero();
        }
        if ($dossierArreteSISH->getArreteMLDate() && $arrete->getDateMainLevee()?->format(AbstractEsaboraService::FORMAT_DATE) !== $dossierArreteSISH->getArreteMLDate()) {
            $changedInformations['arrete_mainlevee_date'] = $dossierArreteSISH->getArreteMLDate();
            $arrete->setDateMainLevee($this->esaboraDateToDateTimeImmutable($dossierArreteSISH->getArreteMLDate()));
        }
        // modificatif (le numéro peut passer de renseigné a null si une date est renseignée)
        if ($dossierArreteSISH->getArreteModificatifDate() && $existingAdditionalInformation['arrete_modificatif_numero'] !== $dossierArreteSISH->getArreteModificatifNumero()) {
            $changedInformations['arrete_modificatif_numero'] = $dossierArreteSISH->getArreteModificatifNumero();
            $existingAdditionalInformation['arrete_modificatif_numero'] = $dossierArreteSISH->getArreteModificatifNumero();
        }
        if ($dossierArreteSISH->getArreteModificatifDate() && $existingAdditionalInformation['arrete_modificatif_date'] !== $dossierArreteSISH->getArreteModificatifDate()) {
            $changedInformations['arrete_modificatif_date'] = $dossierArreteSISH->getArreteModificatifDate();
            $existingAdditionalInformation['arrete_modificatif_date'] = $dossierArreteSISH->getArreteModificatifDate();
        }

        if (0 === count($changedInformations)) {
            return false;
        }
        $arrete->setAdditionalInformation($existingAdditionalInformation);

        $isNewAnnexe = (!$hadMainLevee && isset($changedInformations['arrete_mainlevee_date'])) || (!$hadModificatif && isset($changedInformations['arrete_modificatif_date']));
        $isArreteUnchanged = !isset($changedInformations['arrete_date']) && !isset($changedInformations['arrete_numero']);

        $this->createSuiviAndNotifyArrete(
            $arrete,
            $affectation,
            $isNewAnnexe && $isArreteUnchanged ? [] : $changedInformations,
        );

        return true;
    }

    /**
     * @param array<string, string|null> $changedInformations
     */
    private function createSuiviAndNotifyArrete(Arrete $arrete, Affectation $affectation, array $changedInformations = []): void
    {
        $signalement = $arrete->getSignalement();
        $userSystem = $this->userManager->getSystemUser();

        if (!empty($changedInformations)) {
            $description = ArreteDescriptionGenerator::buildDescriptionArreteUpdated($arrete, $changedInformations);
            $suiviCategory = SuiviCategory::INTERVENTION_ARRETE_IS_RESCHEDULED;
        } else {
            $description = ArreteDescriptionGenerator::buildDescriptionArreteCreated($arrete);
            $suiviCategory = SuiviCategory::INTERVENTION_ARRETE_IS_CREATED;
        }

        $suivi = $this->suiviManager->createSuivi(
            signalement: $signalement,
            description: $description,
            category : $suiviCategory,
            partner: $affectation->getPartner(),
            user: $userSystem,
            isVisibleForUsager: !$signalement->isTiersDeclarant(),
            sendMail: false,
        );

        if ($suivi->getIsVisibleForUsager() && !$signalement->getIsLogementVacant()) {
            $toRecipients = $signalement->getMailUsagers();
            foreach ($toRecipients as $toRecipient) {
                $this->notificationMailerRegistry->send(
                    new NotificationMail(
                        type: NotificationMailerType::TYPE_ARRETE_CREATED_TO_USAGER,
                        to: $toRecipient,
                        territory: $signalement->getAddress()->getTerritory(),
                        signalement: $signalement,
                        params: ['arrete_date' => $arrete->getDateArrete()->format('d/m/Y')],
                    )
                );
            }
        }
        $listUsersToNotify = $this->userRepository->findUsersSubscribedToSignalement($signalement);
        foreach ($listUsersToNotify as $user) {
            if ($user === $userSystem) {
                continue;
            }
            $notification = $this->notificationFactory->createInstanceFrom(user: $user, type: NotificationType::NOUVEAU_SUIVI, suivi: $suivi);
            $this->entityManager->persist($notification);
        }
    }

    /**
     * @throws \InvalidArgumentException
     */
    private function esaboraDateToDateTimeImmutable(?string $date): \DateTimeImmutable
    {
        $dateTime = null !== $date ? \DateTimeImmutable::createFromFormat(AbstractEsaboraService::FORMAT_DATE, $date) : false;
        if (false === $dateTime) {
            throw new \InvalidArgumentException(\sprintf('Date Esabora invalide : "%s" (format attendu : %s)', $date ?? 'null', AbstractEsaboraService::FORMAT_DATE));
        }

        return $dateTime;
    }
}
