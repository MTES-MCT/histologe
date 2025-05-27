<?php

namespace App\Service\Interconnection\Esabora;

use App\Entity\Affectation;
use App\Entity\Enum\InterfacageType;
use App\Entity\Enum\InterventionType;
use App\Entity\Enum\ProcedureType;
use App\Entity\Enum\SuiviCategory;
use App\Entity\File;
use App\Entity\Intervention;
use App\Entity\Suivi;
use App\Entity\User;
use App\Event\InterventionCreatedEvent;
use App\Event\InterventionUpdatedByEsaboraEvent;
use App\Factory\FileFactory;
use App\Factory\InterventionFactory;
use App\Manager\AffectationManager;
use App\Manager\SuiviManager;
use App\Manager\UserManager;
use App\Repository\InterventionRepository;
use App\Service\Files\ZipHelper;
use App\Service\ImageManipulationHandler;
use App\Service\Interconnection\Esabora\Enum\EsaboraStatus;
use App\Service\Interconnection\Esabora\Response\DossierEventFilesSCHSResponse;
use App\Service\Interconnection\Esabora\Response\DossierResponseInterface;
use App\Service\Interconnection\Esabora\Response\Model\DossierArreteSISH;
use App\Service\Interconnection\Esabora\Response\Model\DossierEventSCHS;
use App\Service\Interconnection\Esabora\Response\Model\DossierVisiteSISH;
use App\Service\Intervention\InterventionDescriptionGenerator;
use App\Service\Security\FileScanner;
use App\Service\Signalement\Qualification\SignalementQualificationUpdater;
use App\Service\UploadHandlerService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\File\File as SymfonyFile;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class EsaboraManager
{
    private User $adminUser;

    public function __construct(
        private readonly AffectationManager $affectationManager,
        private readonly SuiviManager $suiviManager,
        private readonly InterventionRepository $interventionRepository,
        private readonly InterventionFactory $interventionFactory,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly UserManager $userManager,
        private readonly LoggerInterface $logger,
        private readonly EntityManagerInterface $entityManager,
        private readonly ZipHelper $zipHelper,
        private readonly FileScanner $fileScanner,
        private readonly UploadHandlerService $uploadHandlerService,
        private readonly ImageManipulationHandler $imageManipulationHandler,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly FileFactory $fileFactory,
        private readonly SignalementQualificationUpdater $signalementQualificationUpdater,
    ) {
        $this->adminUser = $this->userManager->getSystemUser();
    }

    public function synchronizeAffectationFrom(
        DossierResponseInterface $dossierResponse,
        Affectation $affectation,
    ): void {
        $signalement = $affectation->getSignalement();
        $description = $this->updateStatusFor($affectation, $this->adminUser, $dossierResponse);
        if (!empty($description)) {
            $this->suiviManager->createSuivi(
                signalement: $signalement,
                description: 'Signalement <b>'.$description.'</b> par '.$affectation->getPartner()->getNom(),
                type: EsaboraStatus::ESABORA_WAIT->value === $dossierResponse->getSasEtat() ? Suivi::TYPE_TECHNICAL : Suivi::TYPE_AUTO,
                user: $this->adminUser,
                category: SuiviCategory::SIGNALEMENT_STATUS_IS_SYNCHRO
            );
        }
    }

    public function updateStatusFor(
        Affectation $affectation,
        User $user,
        DossierResponseInterface $dossierResponse,
    ): string {
        $description = '';
        $currentStatus = $affectation->getStatut();

        $esaboraStatus = $dossierResponse->getSasEtat();
        $esaboraDossierStatus = null !== $dossierResponse->getEtat()
        ? strtolower($dossierResponse->getEtat())
        : '';

        switch ($esaboraStatus) {
            case EsaboraStatus::ESABORA_WAIT->value:
                if (Affectation::STATUS_WAIT !== $currentStatus) {
                    $this->affectationManager->updateAffectation($affectation, $user, Affectation::STATUS_WAIT);
                    $description = 'remis en attente via '.$dossierResponse->getNameSI();
                }
                break;
            case EsaboraStatus::ESABORA_ACCEPTED->value:
                if ($this->shouldBeAcceptedViaEsabora($esaboraDossierStatus, $currentStatus)) {
                    $this->affectationManager->updateAffectation($affectation, $user, Affectation::STATUS_ACCEPTED);
                    $description = 'accepté via '.$dossierResponse->getNameSI();
                }

                if ($this->shouldBeClosedViaEsabora($esaboraDossierStatus, $currentStatus)) {
                    $this->affectationManager->updateAffectation($affectation, $user, Affectation::STATUS_CLOSED);
                    $description = 'cloturé via '.$dossierResponse->getNameSI();
                }
                break;
            case EsaboraStatus::ESABORA_REFUSED->value:
                if (Affectation::STATUS_REFUSED !== $currentStatus) {
                    $this->affectationManager->updateAffectation($affectation, $user, Affectation::STATUS_REFUSED);
                    $description = 'refusé via '.$dossierResponse->getNameSI();
                }
                break;
            case EsaboraStatus::ESABORA_REJECTED->value:
                if (Affectation::STATUS_REFUSED !== $currentStatus) {
                    $this->affectationManager->updateAffectation($affectation, $user, Affectation::STATUS_REFUSED);
                    $description = \sprintf(
                        'refusé via '.$dossierResponse->getNameSI().' pour motif suivant: %s',
                        $dossierResponse->getSasCauseRefus()
                    );
                }
                break;
        }

        if (!empty($dossierResponse->getDossNum()) && !empty($description)) {
            $description .= ' (Dossier '.$dossierResponse->getDossNum().')';
        }

        return $description;
    }

    /**
     * @throws \Exception
     */
    public function createOrUpdateVisite(Affectation $affectation, DossierVisiteSISH $dossierVisiteSISH): void
    {
        $intervention = $this->interventionRepository->findOneBy(['providerId' => $dossierVisiteSISH->getVisiteId()]);
        if (null !== $intervention) {
            $intervention->setPreviousScheduledAt($intervention->getScheduledAt());
            $isVisiteUpdated = $this->updateFromDossierVisite($intervention, $dossierVisiteSISH, $affectation);
            if ($isVisiteUpdated) {
                $this->eventDispatcher->dispatch(
                    new InterventionUpdatedByEsaboraEvent($intervention, $this->adminUser),
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
                    status: Intervention::STATUS_DONE,
                    providerName: InterfacageType::ESABORA->value,
                    providerId: $dossierVisiteSISH->getVisiteId(),
                    doneBy: $dossierVisiteSISH->getVisitePar(),
                );
                $this->interventionRepository->save($newIntervention, true);
                $this->eventDispatcher->dispatch(
                    new InterventionCreatedEvent($newIntervention, $this->adminUser),
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
        $signalement = $affectation->getSignalement();
        $intervention = $this->interventionRepository->findOneBy(['providerId' => $dossierArreteSISH->getArreteId()]);
        $additionalInformation = [
            'arrete_numero' => $dossierArreteSISH->getArreteNumero(),
            'arrete_type' => $dossierArreteSISH->getArreteType(),
            'arrete_mainlevee_date' => $dossierArreteSISH->getArreteMLDate(),
            'arrete_mainlevee_numero' => $dossierArreteSISH->getArreteMLNumero(),
        ];

        if (null !== $intervention) {
            $isArreteUpdated = $this->updateFromDossierArrete($intervention, $dossierArreteSISH, $additionalInformation);
            if ($isArreteUpdated) {
                $this->eventDispatcher->dispatch(
                    new InterventionUpdatedByEsaboraEvent($intervention, $this->adminUser),
                    InterventionUpdatedByEsaboraEvent::NAME
                );
            }
        } else {
            $intervention = $this->interventionFactory->createInstanceFrom(
                affectation: $affectation,
                type: InterventionType::ARRETE_PREFECTORAL,
                scheduledAt: DateParser::parse($dossierArreteSISH->getArreteDate()),
                registeredAt: new \DateTimeImmutable(),
                status: Intervention::STATUS_DONE,
                providerName: InterfacageType::ESABORA->value,
                providerId: $dossierArreteSISH->getArreteId(),
                details: InterventionDescriptionGenerator::buildDescriptionArreteCreated($dossierArreteSISH),
                additionalInformation: $additionalInformation,
                concludeProcedures: [ProcedureType::INSALUBRITE]
            );

            $this->interventionRepository->save($intervention, true);
            $this->signalementQualificationUpdater->updateQualificationFromVisiteProcedureList(
                $signalement,
                [ProcedureType::INSALUBRITE]
            );
            $this->eventDispatcher->dispatch(
                new InterventionCreatedEvent($intervention, $this->adminUser),
                InterventionCreatedEvent::NAME
            );
        }
    }

    public function createSuiviFromDossierEvent(DossierEventSCHS $event, Affectation $affectation): Suivi
    {
        $description = 'Message provenant d\'esabora SCHS :'.\PHP_EOL;

        if (!empty($event->getPresentation())) {
            $description .= $event->getPresentation().\PHP_EOL;
        }

        if (!empty($event->getLibelle())) {
            $description .= $event->getLibelle();
        }

        $suivi = $this->suiviManager->createSuivi(
            signalement: $affectation->getSignalement(),
            description: $description,
            type: Suivi::TYPE_PARTNER,
            user: $this->adminUser,
            context: Suivi::CONTEXT_SCHS,
            flush: false,
        );
        $suivi->setCreatedAt(\DateTimeImmutable::createFromFormat('d/m/Y', $event->getDate()));
        $suivi->setOriginalData($event->getOriginalData());
        $this->entityManager->persist($suivi);

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
            $this->logger->error("'File '.$originalName.' from SCHS is infected'");

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
            scannedAt: new \DateTimeImmutable(),
            isVariantsGenerated: $variantsGenerated,
        );
        $this->entityManager->persist($file);
        $urlDocument = $this->urlGenerator->generate(
            'show_file',
            ['uuid' => $file->getUuid()],
            UrlGeneratorInterface::ABSOLUTE_URL
        );
        $linkToFile = '<br /><a class="fr-link" target="_blank" rel="noopener" href="'
            .$urlDocument.'">'
            .$file->getTitle()
            .'</a>';
        $suivi->setDescription($suivi->getDescription().$linkToFile);

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
            $this->interventionRepository->save($intervention, true);
        }

        return $hasChanged;
    }

    /**
     * @throws \Exception
     */
    private function updateFromDossierArrete(Intervention $intervention, DossierArreteSISH $dossierArreteSISH, array $additionalInformation): bool
    {
        $hasChanged = false;

        if ($intervention->getAdditionalInformation() !== $additionalInformation) {
            $intervention->setAdditionalInformation($additionalInformation);
            $hasChanged = true;
        }

        $scheduledAt = DateParser::parse($dossierArreteSISH->getArreteDate());
        if ($intervention->getScheduledAt() != $scheduledAt) {
            $intervention->setScheduledAt($scheduledAt);
            $hasChanged = true;
        }

        $newDetails = InterventionDescriptionGenerator::buildDescriptionArreteCreated($dossierArreteSISH);
        if ($intervention->getDetails() !== $newDetails) {
            $intervention->setDetails($newDetails);
            $hasChanged = true;
        }

        if (Intervention::STATUS_DONE !== $intervention->getStatus()) {
            $intervention->setStatus(Intervention::STATUS_DONE);
            $hasChanged = true;
        }

        if ($hasChanged) {
            $this->interventionRepository->save($intervention, true);
        }

        return $hasChanged;
    }

    private function shouldBeAcceptedViaEsabora(string $esaboraDossierStatus, int $currentStatus): bool
    {
        return EsaboraStatus::ESABORA_IN_PROGRESS->value === $esaboraDossierStatus
            && Affectation::STATUS_ACCEPTED !== $currentStatus;
    }

    private function shouldBeClosedViaEsabora(string $esaboraDossierStatus, int $currentStatus): bool
    {
        return EsaboraStatus::ESABORA_CLOSED->value === $esaboraDossierStatus
            && Affectation::STATUS_CLOSED !== $currentStatus;
    }
}
