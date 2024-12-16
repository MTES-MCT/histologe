<?php

namespace App\Service\Interconnection\Esabora;

use App\Entity\Affectation;
use App\Entity\Enum\InterfacageType;
use App\Entity\Enum\InterventionType;
use App\Entity\Enum\ProcedureType;
use App\Entity\File;
use App\Entity\Intervention;
use App\Entity\Suivi;
use App\Entity\User;
use App\Event\InterventionCreatedEvent;
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
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\File\File as SymfonyFile;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class EsaboraManager
{
    public function __construct(
        private readonly AffectationManager $affectationManager,
        private readonly SuiviManager $suiviManager,
        private readonly InterventionRepository $interventionRepository,
        private readonly InterventionFactory $interventionFactory,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly UserManager $userManager,
        private readonly LoggerInterface $logger,
        private readonly ParameterBagInterface $parameterBag,
        private readonly EntityManagerInterface $entityManager,
        private readonly ZipHelper $zipHelper,
        private readonly FileScanner $fileScanner,
        private readonly UploadHandlerService $uploadHandlerService,
        private readonly ImageManipulationHandler $imageManipulationHandler,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly FileFactory $fileFactory,
        private readonly SignalementQualificationUpdater $signalementQualificationUpdater,
    ) {
    }

    public function synchronizeAffectationFrom(
        DossierResponseInterface $dossierResponse,
        Affectation $affectation,
    ): void {
        $adminUser = $this->userManager->findOneBy(['email' => $this->parameterBag->get('user_system_email')]);
        $signalement = $affectation->getSignalement();

        $description = $this->updateStatusFor($affectation, $adminUser, $dossierResponse);
        if (!empty($description)) {
            $params = [
                'domain' => 'esabora',
                'action' => 'synchronize',
                'description' => $description,
                'name_partner' => $affectation->getPartner()->getNom(),
            ];

            if (EsaboraStatus::ESABORA_WAIT->value === $dossierResponse->getSasEtat()) {
                $params['type'] = Suivi::TYPE_TECHNICAL;
            }

            $suivi = $this->suiviManager->createSuivi(
                user: $adminUser,
                signalement: $signalement,
                params: $params,
            );
            $this->suiviManager->save($suivi);
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
        : null;

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
            $this->updateFromDossierVisite($intervention, $dossierVisiteSISH);
            $this->eventDispatcher->dispatch(
                new InterventionCreatedEvent($intervention, $this->userManager->getSystemUser()),
                InterventionCreatedEvent::UPDATED_BY_ESABORA
            );
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
                    new InterventionCreatedEvent($newIntervention, $this->userManager->getSystemUser()),
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
            $intervention->setAdditionalInformation($additionalInformation);
            $this->updateFromDossierArrete($intervention, $dossierArreteSISH);
            $this->eventDispatcher->dispatch(
                new InterventionCreatedEvent($intervention, $this->userManager->getSystemUser()),
                InterventionCreatedEvent::UPDATED_BY_ESABORA
            );
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
                new InterventionCreatedEvent($intervention, $this->userManager->getSystemUser()),
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

        $suivi = new Suivi();
        $suivi->setCreatedBy($this->userManager->getSystemUser());
        $suivi->setSignalement($affectation->getSignalement());
        $suivi->setType(Suivi::TYPE_PARTNER);
        $suivi->setContext(Suivi::CONTEXT_SCHS);
        $suivi->setDescription(nl2br($description));
        if (!empty($event->getDate())) {
            $suivi->setCreatedAt(\DateTimeImmutable::createFromFormat('d/m/Y', $event->getDate()));
        }
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
        if (\in_array($file->getMimeType(), File::IMAGE_MIME_TYPES)) {
            $this->imageManipulationHandler->resize($file->getPath())->thumbnail();
            $variantsGenerated = true;
        }
        $file = $this->fileFactory->createInstanceFrom(
            filename: $fileName,
            title: $originalName,
            type: File::FILE_TYPE_DOCUMENT,
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
    private function updateFromDossierVisite(Intervention $intervention, DossierVisiteSISH $dossierVisiteSISH): void
    {
        $intervention
            ->setScheduledAt(DateParser::parse($dossierVisiteSISH->getVisiteDate()))
            ->setDoneBy($dossierVisiteSISH->getVisitePar());

        $this->interventionRepository->save($intervention, true);
    }

    /**
     * @throws \Exception
     */
    private function updateFromDossierArrete(Intervention $intervention, DossierArreteSISH $dossierArreteSISH): void
    {
        $intervention
            ->setScheduledAt(DateParser::parse($dossierArreteSISH->getArreteDate()))
            ->setDetails(InterventionDescriptionGenerator::buildDescriptionArreteCreated($dossierArreteSISH))
            ->setStatus(Intervention::STATUS_DONE);

        $this->interventionRepository->save($intervention, true);
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
