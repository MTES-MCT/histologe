<?php

namespace App\Command\Cron;

use App\Entity\Affectation;
use App\Entity\Enum\DocumentType;
use App\Entity\Enum\PartnerType;
use App\Entity\File;
use App\Entity\Suivi;
use App\Manager\JobEventManager;
use App\Repository\AffectationRepository;
use App\Repository\SuiviRepository;
use App\Service\Esabora\EsaboraManager;
use App\Service\Esabora\EsaboraSCHSService;
use App\Service\Files\ZipHelper;
use App\Service\ImageManipulationHandler;
use App\Service\Mailer\NotificationMail;
use App\Service\Mailer\NotificationMailerRegistry;
use App\Service\Mailer\NotificationMailerType;
use App\Service\Security\FileScanner;
use App\Service\UploadHandlerService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\File\File as SymfonyFile;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\SerializerInterface;

#[AsCommand(
    name: 'app:sync-esabora-schs',
    description: '[SCHS] Commande qui permet de mettre à jour l\'état d\'une affectation depuis Esabora',
)]
class SynchronizeEsaboraSCHSCommand extends AbstractSynchronizeEsaboraCommand
{
    private SymfonyStyle $io;
    private array $existingEvents = [];
    private int $searchId;
    private string $documentTypeName;
    private int $nbEventsAdded = 0;
    private int $nbEventFilesAdded = 0;

    public function __construct(
        private readonly EsaboraSCHSService $esaboraService,
        private readonly EsaboraManager $esaboraManager,
        private readonly JobEventManager $jobEventManager,
        private readonly AffectationRepository $affectationRepository,
        private readonly SerializerInterface $serializer,
        private readonly NotificationMailerRegistry $notificationMailerRegistry,
        private readonly ParameterBagInterface $parameterBag,
        private readonly LoggerInterface $logger,
        private readonly SuiviRepository $suiviRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly ZipHelper $zipHelper,
        private readonly FileScanner $fileScanner,
        private readonly UploadHandlerService $uploadHandlerService,
        private readonly ImageManipulationHandler $imageManipulationHandler,
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {
        parent::__construct(
            $this->esaboraManager,
            $this->jobEventManager,
            $this->affectationRepository,
            $this->serializer,
            $this->notificationMailerRegistry,
            $this->parameterBag,
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->io = new SymfonyStyle($input, $output);

        $this->synchronizeStatus(
            $input,
            $output,
            $this->esaboraService,
            PartnerType::COMMUNE_SCHS,
            'SAS_Référence'
        );

        $this->synchronizeEvents();

        return Command::SUCCESS;
    }

    protected function synchronizeEvents(): void
    {
        $affectations = $this->affectationRepository->findAffectationSubscribedToEsabora(partnerType: PartnerType::COMMUNE_SCHS, isSynchronized: true);
        $this->existingEvents = $this->suiviRepository->findExistingEventsForSCHS();
        foreach ($affectations as $affectation) {
            try {
                $response = $this->esaboraService->getEventsDossier($affectation);
                $statusCode = $response->getStatusCode();
                if (Response::HTTP_OK !== $statusCode) {
                    throw new \Exception('status code : '.$statusCode);
                }
                $dataResponse = $response->toArray();
                if (!isset($dataResponse['searchId']) || !isset($dataResponse['documentTypeList']) || !isset($dataResponse['rowList']) || empty($dataResponse['rowList'])) {
                    continue;
                }
                $this->searchId = $dataResponse['searchId'];
                $this->documentTypeName = reset($dataResponse['documentTypeList']);
                foreach ($dataResponse['rowList'] as $event) {
                    $this->synchronizeEvent($event, $affectation);
                }
                $this->entityManager->flush();
            } catch (\Throwable $exception) {
                $msg = sprintf('Error while synchronizing events on signalement %s: %s', $affectation->getSignalement()->getUuid(), $exception->getMessage());
                $this->io->error($msg);
                $this->logger->error($msg);
            }
        }
        $this->entityManager->flush();
        $msg = sprintf('Synchronized %d new events with %d files', $this->nbEventsAdded, $this->nbEventFilesAdded);
        $this->io->success($msg);
        $this->notificationMailerRegistry->send(
            new NotificationMail(
                type: NotificationMailerType::TYPE_CRON,
                to: $this->parameterBag->get('admin_email'),
                message: $msg,
                cronLabel: '[SCHS] Synchronisation des évènements depuis Esabora',
            )
        );
    }

    protected function synchronizeEvent(array $event, Affectation $affectation): void
    {
        // données de l'événenement (dans la clé "columnDataList") :
        // - reference histologe
        // - date
        // - description
        // - nom des pieces jointe (séparé par des virgules)
        // - type d'événement
        // id techniques (dans la clé "keyDataList") :
        // - Identifiant technique de l'import dans le sas
        // - Identifiant technique de l’évènement dans le sas
        if (!isset($this->existingEvents[$event['keyDataList'][1]])) {
            $suivi = new Suivi();
            $suivi->setSignalement($affectation->getSignalement());
            $suivi->setType(Suivi::TYPE_PARTNER); // bon type de suivi ?
            $suivi->setContext(Suivi::CONTEXT_SCHS);
            $suivi->setDescription(nl2br($event['columnDataList'][2]));
            $suivi->setCreatedAt(\DateTimeImmutable::createFromFormat('d/m/Y', $event['columnDataList'][1]));
            $suivi->setOriginalData($event);
            $this->entityManager->persist($suivi);
            ++$this->nbEventsAdded;
            if (!empty($event['columnDataList'][3])) {
                $this->SynchronizeEventFiles($suivi, $affectation);
            }
        }
    }

    protected function SynchronizeEventFiles(Suivi $suivi, Affectation $affectation): bool
    {
        try {
            $response = $this->esaboraService->getEventFiles($suivi, $affectation, $this->searchId, $this->documentTypeName);
            $statusCode = $response->getStatusCode();
            if (Response::HTTP_OK !== $statusCode) {
                throw new \Exception('status code : '.$statusCode);
            }
            $dataResponse = $response->toArray();
            if (!isset($dataResponse['rowList']) || !isset($dataResponse['rowList'][0]) || !isset($dataResponse['rowList'][0]['documentZipContent'])) {
                return false;
            }
            $documentZipContent = $dataResponse['rowList'][0]['documentZipContent'];
            $zipFilePath = $this->zipHelper->getZipFromBase64($documentZipContent);
            $files = $this->zipHelper->extractZipFiles($zipFilePath);
            foreach ($files as $filepath => $filename) {
                $this->addEventFile($filepath, $filename, $suivi);
            }
        } catch (\Throwable $exception) {
            $msg = sprintf('Error while synchronizing events files on signalement %s: %s', $affectation->getSignalement()->getUuid(), $exception->getMessage());
            $this->io->error($msg);
            $this->logger->error($msg);
        }

        return true;
    }

    protected function addEventFile(string $filePath, string $originalName, Suivi $suivi): bool
    {
        if (!$this->fileScanner->isClean($filePath, false)) {
            $msg = "'File '.$originalName.' from SCHS is infected'";
            $this->io->error($msg);
            $this->logger->error($msg);

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
        $file = new File();
        $file->setSignalement($suivi->getSignalement());
        $file->setFilename($fileName);
        $file->setTitle($originalName);
        $file->setFileType(File::FILE_TYPE_DOCUMENT);
        $file->setDocumentType(DocumentType::AUTRE);
        $file->setIsVariantsGenerated($variantsGenerated);
        $file->setScannedAt(new \DateTimeImmutable());
        $this->entityManager->persist($file);

        $urlDocument = $this->urlGenerator->generate('show_file', ['uuid' => $file->getUuid()], UrlGeneratorInterface::ABSOLUTE_URL);
        $linkToFile = '<br /><a class="fr-link" target="_blank" rel="noopener" href="'.$urlDocument.'">'.$file->getTitle().'</a>';
        $suivi->setDescription($suivi->getDescription().$linkToFile);

        ++$this->nbEventFilesAdded;

        return true;
    }
}
