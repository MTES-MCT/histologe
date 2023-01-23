<?php

namespace App\Command;

use App\Entity\Signalement;
use App\Entity\Territory;
use App\Manager\SignalementManager;
use App\Manager\TerritoryManager;
use App\Repository\SignalementRepository;
use App\Service\Import\CsvParser;
use App\Service\UploadHandlerService;
use Doctrine\ORM\NonUniqueResultException;
use League\Flysystem\FilesystemException;
use League\Flysystem\FilesystemOperator;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

#[AsCommand(
    name: 'app:update-doc-signalement',
    description: 'Parse CSV and link image to signalement',
)]
class UpdateSignalementDocumentFieldsCommand extends Command
{
    private const BATCH_SIZE = 200;
    private const TYPE_IMAGE = 'image';
    private const REGEX_IMAGE = '/(jpe?g|png)/mi';

    public function __construct(
        private TerritoryManager $territoryManager,
        private SignalementManager $signalementManager,
        private CsvParser $csvParser,
        private ParameterBagInterface $parameterBag,
        private FilesystemOperator $fileStorage,
        private UploadHandlerService $uploadHandlerService,
        private LoggerInterface $logger,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('zip', InputArgument::REQUIRED, 'Territory zip to target');
    }

    /**
     * @throws FilesystemException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $zip = $input->getArgument('zip');
        /** @var Territory $territory */
        $territory = $this->territoryManager->findOneBy(['zip' => $zip]);
        if (null === $territory) {
            $io->error('Territory does not exists');

            return Command::FAILURE;
        }

        $fromFile = 'csv/'.SlugifyDocumentSignalementCommand::PREFIX_FILENAME_STORAGE.'_'.$zip.'.csv';
        $toFile = $this->parameterBag->get('uploads_tmp_dir').'mapping_doc_signalement_'.$zip.'.csv';
        if (!$this->fileStorage->fileExists($fromFile)) {
            $io->error('CSV Mapping file does not exists, please execute app:slugify-doc-signalement');

            return Command::FAILURE;
        }

        $this->uploadHandlerService->createTmpFileFromBucket($fromFile, $toFile);

        $rows = $this->csvParser->parseAsDict($toFile);

        /** @var SignalementRepository $signalementRepository */
        $signalementRepository = $this->signalementManager->getRepository();
        $photos = [];
        $documents = [];
        $currentReference = $rows[0]['id_Enregistrement'];
        foreach ($rows as $key => $row) {
            try {
                if ($row['id_Enregistrement'] === $currentReference) {
                    $filename = $row['sAttachFileName'];
                    $fileInfo = pathinfo($filename);
                    if (4 !== \count($fileInfo)) {
                        continue;
                    }
                    $this->logger->info(sprintf('%s slugged', $filename));
                    if (self::TYPE_IMAGE === $this->checkFileType($filename)) {
                        $photo = [
                            'file' => $filename,
                            'titre' => $filename,
                            'date' => date('d-m-Y'),
                        ];
                        $photos[] = $photo;
                    } else {
                        $document = [
                            'file' => $filename,
                            'titre' => $filename,
                            'date' => date('d-m-Y'),
                        ];
                        $documents[] = $document;
                    }
                } else {
                    $signalement = $signalementRepository->findByReferenceChunk($territory, $row['id_Enregistrement']);
                    if ($signalement instanceof Signalement) {
                        $signalement
                            ->setPhotos($photos)
                            ->setDocuments($documents);

                        if (0 === $key % self::BATCH_SIZE) {
                            $this->signalementManager->flush();
                            $io->success(sprintf('%s flushed', self::BATCH_SIZE));
                        } else {
                            $this->signalementManager->persist($signalement);
                            $io->success($signalement->getReference().' updated');
                            unset($signalement);
                        }
                    }
                    $photos = [];
                    $documents = [];
                    $currentReference = $row['id_Enregistrement'];
                }
            } catch (NonUniqueResultException $exception) {
                $this->logger->warning($row['id_Enregistrement'].':'.$exception->getMessage());
            }
        }

        $this->signalementManager->flush();

        return Command::SUCCESS;
    }

    private function checkFileType(string $filePath): string
    {
        $fileInfo = pathinfo($filePath);
        if (preg_match(self::REGEX_IMAGE, $fileInfo['extension'])) {
            return 'image';
        }

        return 'document';
    }
}
