<?php

namespace App\Service\Interconnection\Esabora;

use App\Entity\Affectation;
use App\Entity\Enum\AffectationStatus;
use App\Entity\Enum\ArreteCreationSource;
use App\Entity\Enum\InterfacageType;
use App\Entity\Enum\InterventionType;
use App\Entity\Enum\SuiviCategory;
use App\Entity\File;
use App\Entity\Intervention;
use App\Entity\Suivi;
use App\Entity\SuiviFile;
use App\Entity\User;
use App\Event\InterventionCreatedEvent;
use App\Event\InterventionUpdatedByEsaboraEvent;
use App\Factory\FileFactory;
use App\Factory\InterventionFactory;
use App\Manager\AffectationManager;
use App\Manager\ArreteManager;
use App\Manager\SuiviManager;
use App\Manager\UserManager;
use App\Manager\UserSignalementSubscriptionManager;
use App\Repository\ArreteRepository;
use App\Repository\InterventionRepository;
use App\Repository\SuiviRepository;
use App\Service\Files\ImageManipulationHandler;
use App\Service\Files\ZipHelper;
use App\Service\Interconnection\Esabora\Enum\EsaboraStatus;
use App\Service\Interconnection\Esabora\Response\DossierEventFilesSCHSResponse;
use App\Service\Interconnection\Esabora\Response\DossierResponseInterface;
use App\Service\Interconnection\Esabora\Response\Model\DossierArreteSISH;
use App\Service\Interconnection\Esabora\Response\Model\DossierEventSCHS;
use App\Service\Interconnection\Esabora\Response\Model\DossierVisiteSISH;
use App\Service\Security\FileScanner;
use App\Service\Signalement\Suivi\SuiviDescriptionHelper;
use App\Service\UploadHandlerService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\File\File as SymfonyFile;
use Symfony\Component\Workflow\WorkflowInterface;

class EsaboraManager
{
    public function __construct(
        private readonly AffectationManager $affectationManager,
        private readonly SuiviManager $suiviManager,
        private readonly SuiviRepository $suiviRepository,
        private readonly InterventionRepository $interventionRepository,
        private readonly ArreteRepository $arreteRepository,
        private readonly InterventionFactory $interventionFactory,
        private readonly ArreteManager $arreteManager,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly UserManager $userManager,
        private readonly LoggerInterface $logger,
        private readonly EntityManagerInterface $entityManager,
        private readonly ZipHelper $zipHelper,
        private readonly FileScanner $fileScanner,
        private readonly UploadHandlerService $uploadHandlerService,
        private readonly ImageManipulationHandler $imageManipulationHandler,
        private readonly FileFactory $fileFactory,
        #[Autowire(service: 'state_machine.intervention_planning')]
        private readonly WorkflowInterface $workflow,
        private readonly UserSignalementSubscriptionManager $userSignalementSubscriptionManager,
        #[Autowire(env: 'FEATURE_SISH_REPUSH_MESSAGE')]
        private readonly bool $featureSishRepushMessage,
        private readonly SuiviDescriptionHelper $suiviDescriptionHelper,
    ) {
    }

    /**
     * @throws \DateMalformedStringException
     * @throws \DateInvalidTimeZoneException
     */
    public function synchronizeAffectationFrom(
        DossierResponseInterface $dossierResponse,
        Affectation $affectation,
    ): void {
        $signalement = $affectation->getSignalement();
        $adminUser = $this->userManager->getSystemUser();
        $description = $this->updateStatusAndBuildDescription($affectation, $adminUser, $dossierResponse);
        if (!empty($description)) {
            $this->suiviManager->createSuivi(
                signalement: $signalement,
                description: $description,
                category: SuiviCategory::SIGNALEMENT_STATUS_IS_SYNCHRO,
                partner: $affectation->getPartner(),
                user: $adminUser,
                sendMail: EsaboraStatus::ESABORA_WAIT->value === $dossierResponse->getSasEtat() ? false : true,
            );
        }
    }

    /**
     * @throws \DateInvalidTimeZoneException
     * @throws \DateMalformedStringException
     */
    public function updateStatusAndBuildDescription(
        Affectation $affectation,
        User $user,
        DossierResponseInterface $dossierResponse,
    ): string {
        $action = null;
        $dateReaffectation = null;
        $currentStatus = $affectation->getStatut();

        $esaboraStatus = $dossierResponse->getSasEtat();
        $esaboraDossierStatus = null !== $dossierResponse->getEtat()
        ? strtolower($dossierResponse->getEtat())
        : '';

        $namePartner = $affectation->getPartner()->getNom();
        $signalement = $affectation->getSignalement();
        switch ($esaboraStatus) {
            case EsaboraStatus::ESABORA_WAIT->value:
                if (AffectationStatus::WAIT !== $currentStatus) {
                    $this->affectationManager->updateAffectation($affectation, $user, AffectationStatus::WAIT, $affectation->getPartner());
                    $this->affectationManager->removeSubscriptionsOfAffectation($affectation);
                    $action = 'wait';
                }
                break;
            case EsaboraStatus::ESABORA_ACCEPTED->value:
                if ($this->shouldBeAcceptedViaEsabora($esaboraDossierStatus, $currentStatus)) {
                    $this->affectationManager->updateAffectation($affectation, $user, AffectationStatus::ACCEPTED, $affectation->getPartner());
                    $this->userSignalementSubscriptionManager->createDefaultSubscriptionsForAffectation($affectation);
                    $this->entityManager->flush();

                    // Doit correspondre au début du texte généré par suivi/signalement_status_is_synchro.html.twig
                    $suivis = $this->suiviRepository->findSuiviByDescription(
                        $signalement,
                        'Signalement <b>accepté par '.$namePartner.' via '.$dossierResponse->getNameSI(),
                        SuiviCategory::SIGNALEMENT_STATUS_IS_SYNCHRO
                    );
                    $action = count($suivis) > 0 ? 'resynchronized' : 'accepted';
                }

                if ($this->shouldBeClosedViaEsabora($esaboraDossierStatus, $currentStatus)) {
                    $this->affectationManager->updateAffectation($affectation, $user, AffectationStatus::CLOSED, $affectation->getPartner());
                    $this->affectationManager->removeSubscriptionsOfAffectation($affectation);
                    $action = 'closed';
                }
                break;
            case EsaboraStatus::ESABORA_REFUSED->value:
                if (AffectationStatus::REFUSED !== $currentStatus) {
                    $this->affectationManager->updateAffectation($affectation, $user, AffectationStatus::REFUSED, $affectation->getPartner());
                    $action = 'refused';
                }
                break;
            case EsaboraStatus::ESABORA_REJECTED->value:
                if (AffectationStatus::REFUSED !== $currentStatus) {
                    $this->affectationManager->updateAffectation(
                        affectation: $affectation,
                        user: $user,
                        status: AffectationStatus::REFUSED,
                        partner: $affectation->getPartner(),
                        dispatchAffectationAnsweredEvent: false
                    );
                    if ($this->featureSishRepushMessage) {
                        $timezone = new \DateTimeZone($affectation->getSignalement()->getTimezone());
                        $dateReaffectation = (new \DateTimeImmutable('now', $timezone))
                            ->modify('+1 day')
                            ->format('d/m/Y');
                    }
                    $action = 'rejected';
                }
                break;
        }

        if (null === $action) {
            return '';
        }

        return $this->suiviDescriptionHelper->buildDescriptionForCreation(SuiviCategory::SIGNALEMENT_STATUS_IS_SYNCHRO, [
            'action' => $action,
            'partnerName' => $namePartner,
            'nameSI' => $dossierResponse->getNameSI(),
            'causeRefus' => $dossierResponse->getSasCauseRefus(),
            'dateReaffectation' => $dateReaffectation,
            'commentaireSas' => $dossierResponse->getCommentaireSas(),
            'dossNum' => $dossierResponse->getDossNum(),
            'type' => $dossierResponse->getType(),
            'problematique' => $dossierResponse->getProblematique(),
        ]);
    }

    /**
     * @throws \Exception
     */
    public function createOrUpdateVisite(Affectation $affectation, DossierVisiteSISH $dossierVisiteSISH): void
    {
        $intervention = $this->interventionRepository->findOneBy(['providerId' => $dossierVisiteSISH->getVisiteId()]);
        if (null !== $intervention) {
            $isVisiteUpdated = $this->updateFromDossierVisite($intervention, $dossierVisiteSISH, $affectation);
            if ($isVisiteUpdated) {
                $this->eventDispatcher->dispatch(
                    new InterventionUpdatedByEsaboraEvent($intervention, $this->userManager->getSystemUser(), $affectation->getPartner()),
                    InterventionUpdatedByEsaboraEvent::NAME
                );
            }
        } else {
            if (null === InterventionType::tryFromLabel($dossierVisiteSISH->getVisiteType())) {
                $this->logger->error(
                    \sprintf(
                        '#%s - Le dossier SISH %s a un type de visite invalide `%s`. Types valides : (%s)',
                        $dossierVisiteSISH->getReferenceDossier(),
                        $dossierVisiteSISH->getDossNum(),
                        $dossierVisiteSISH->getVisiteType(),
                        implode(',', InterventionType::getLabelList())
                    )
                );
            } else {
                $newIntervention = $this->interventionFactory->createInstanceFrom(
                    affectation: $affectation,
                    type: InterventionType::tryFromLabel($dossierVisiteSISH->getVisiteType()),
                    scheduledAt: DateParser::parse(
                        $dossierVisiteSISH->getVisiteDate(),
                        $affectation->getSignalement()->getTimezone()
                    ),
                    registeredAt: new \DateTimeImmutable(),
                    status: Intervention::STATUS_PLANNED,
                    providerName: InterfacageType::ESABORA->value,
                    providerId: $dossierVisiteSISH->getVisiteId(),
                    doneBy: $dossierVisiteSISH->getVisitePar(),
                );
                $this->entityManager->persist($newIntervention);
                $this->entityManager->flush();

                if ($this->workflow->can($newIntervention, 'confirm')) {
                    $this->workflow->apply($newIntervention, 'confirm', [
                        'esabora' => true,
                    ]);
                }

                $this->eventDispatcher->dispatch(
                    new InterventionCreatedEvent(
                        $newIntervention,
                        $this->userManager->getSystemUser(),
                        $affectation->getPartner(),
                        EsaboraSISHService::NAME_SI
                    ),
                    InterventionCreatedEvent::NAME
                );
            }
        }
    }

    /**
     * @throws \Exception
     */
    public function createOrUpdateArrete(Affectation $affectation, DossierArreteSISH $dossierArreteSISH): void
    {
        $arrete = $this->arreteRepository->findOneBy(['providerId' => $dossierArreteSISH->getArreteId(), 'arreteCreationSource' => ArreteCreationSource::ESABORA]);

        if (null !== $arrete) {
            try {
                $this->arreteManager->updateFromDossierArreteSISH($arrete, $dossierArreteSISH, $affectation);
                $this->entityManager->flush();
            } catch (\Exception $e) {
                $this->logger->error('Error updating Arrete from Esabora : '.$e->getMessage());
            }
        } else {
            try {
                $this->arreteManager->createArreteFromDossierArreteSISH($dossierArreteSISH, $affectation);
                $this->entityManager->flush();
            } catch (\Exception $e) {
                $this->logger->error('Error creating Arrete from Esabora : '.$e->getMessage());
            }
        }
    }

    /**
     * @throws \Exception
     */
    public function createSuiviFromDossierEvent(DossierEventSCHS $event, Affectation $affectation): Suivi
    {
        $rawDate = $event->getDate();
        $dateLabel = 'inconnue';
        if (!empty($rawDate)
            && false !== \DateTimeImmutable::createFromFormat(AbstractEsaboraService::FORMAT_DATE, $rawDate)
        ) {
            $dateLabel = $rawDate;
        }

        $category = SuiviCategory::MESSAGE_ESABORA_SCHS;
        $suivi = $this->suiviManager->createSuivi(
            signalement: $affectation->getSignalement(),
            description: $this->suiviDescriptionHelper->buildDescriptionForCreation($category, [
                'date' => $dateLabel,
                'presentation' => $event->getPresentation(),
                'libelle' => $event->getLibelle(),
            ]),
            category: $category,
            partner: $affectation->getPartner(),
            user: $this->userManager->getSystemUser(),
        );

        $suivi->setOriginalData($event->getOriginalData());

        return $suivi;
    }

    /**
     * @throws \Exception
     * @throws \Throwable
     */
    public function addFilesToSuiviFromDossierEventFiles(DossierEventFilesSCHSResponse $eventFiles, Suivi $suivi): int
    {
        $nbFilesAdded = 0;
        $zipFilePath = $this->zipHelper->getZipFromBase64($eventFiles->getDocumentZipContent());
        $files = $this->zipHelper->extractZipFiles($zipFilePath);
        foreach ($files as $filepath => $filename) {
            if ($this->addFileToSuivi($filepath, $filename, $suivi)) {
                ++$nbFilesAdded;
            }
        }

        return $nbFilesAdded;
    }

    /**
     * @throws \Throwable
     */
    private function addFileToSuivi(string $filePath, string $originalName, Suivi $suivi): bool
    {
        if (!$this->fileScanner->isClean($filePath, false)) {
            $this->logger->error("'File from SCHS is infected'");

            return false;
        }
        $variantsGenerated = false;
        $fileName = pathinfo($filePath, \PATHINFO_BASENAME);
        $this->uploadHandlerService->uploadFromFilename($fileName);
        $file = new SymfonyFile($filePath);
        if (\in_array($file->getMimeType(), File::RESIZABLE_MIME_TYPES)) {
            $this->imageManipulationHandler->resize($file->getPath())->thumbnail();
            $variantsGenerated = true;
        }
        $file = $this->fileFactory->createInstanceFrom(
            filename: $fileName,
            title: $originalName,
            signalement: $suivi->getSignalement(),
            partner: $suivi->getPartner(),
            scannedAt: new \DateTimeImmutable(),
            isVariantsGenerated: $variantsGenerated,
        );
        $this->entityManager->persist($file);
        $suiviFile = (new SuiviFile())->setFile($file)->setSuivi($suivi)->setTitle($file->getTitle());
        $this->entityManager->persist($suiviFile);
        $suivi->addSuiviFile($suiviFile);

        return true;
    }

    /**
     * @throws \Exception
     */
    private function updateFromDossierVisite(Intervention $intervention, DossierVisiteSISH $dossierVisiteSISH, Affectation $affectation): bool
    {
        $hasChanged = false;

        $scheduledAt = DateParser::parse(
            $dossierVisiteSISH->getVisiteDate(),
            $affectation->getSignalement()->getTimezone()
        );
        if ($intervention->getScheduledAt()?->getTimestamp() !== $scheduledAt->getTimestamp()) {
            $intervention->setPreviousScheduledAt($intervention->getScheduledAt());
            $intervention->setScheduledAt($scheduledAt);
            $hasChanged = true;
        }

        $visitePar = $dossierVisiteSISH->getVisitePar();
        if ($intervention->getDoneBy() !== $visitePar) {
            $intervention->setDoneBy($visitePar);
            $hasChanged = true;

            if ('ARS' !== $visitePar && null !== $visitePar) {
                if ($intervention->getExternalOperator() !== $visitePar) {
                    $intervention->setExternalOperator($visitePar);
                    $intervention->setPartner(null);
                }
            }
        }

        if ($hasChanged) {
            $this->entityManager->flush();
        }

        return $hasChanged;
    }

    private function shouldBeAcceptedViaEsabora(string $esaboraDossierStatus, AffectationStatus $currentStatus): bool
    {
        return EsaboraStatus::ESABORA_IN_PROGRESS->value === $esaboraDossierStatus
            && AffectationStatus::ACCEPTED !== $currentStatus;
    }

    private function shouldBeClosedViaEsabora(string $esaboraDossierStatus, AffectationStatus $currentStatus): bool
    {
        return EsaboraStatus::ESABORA_CLOSED->value === $esaboraDossierStatus
            && AffectationStatus::CLOSED !== $currentStatus;
    }
}
