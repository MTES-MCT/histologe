<?php

namespace App\Command;

use App\Entity\File;
use App\Entity\Signalement;
use App\Entity\Territory;
use App\Manager\SignalementManager;
use App\Manager\TerritoryManager;
use App\Repository\SignalementRepository;
use App\Service\Import\CsvParser;
use App\Service\Import\Signalement\SignalementImportImageHeader;
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
    private const TYPE_DOCUMENT = 'document';
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
            $io->error('Territory does not exist');

            return Command::FAILURE;
        }

        $fromFile = 'csv/'.SlugifyDocumentSignalementCommand::PREFIX_FILENAME_STORAGE.$zip.'.csv';
        $toFile = $this->parameterBag->get('uploads_tmp_dir').'mapping_doc_signalement_'.$zip.'.csv';
        if (!$this->fileStorage->fileExists($fromFile)) {
            $io->error('CSV Mapping file '.$fromFile.' does not exist, please execute app:slugify-doc-signalement');

            return Command::FAILURE;
        }

        $this->uploadHandlerService->createTmpFileFromBucket($fromFile, $toFile);

        $rows = $this->csvParser->parseAsDict($toFile);

        /** @var SignalementRepository $signalementRepository */
        $signalementRepository = $this->signalementManager->getRepository();
        $files = [];
        $currentReference = $rows[0][SignalementImportImageHeader::COLUMN_ID_ENREGISTREMENT];
        $countSignalement = 0;
        foreach ($rows as $key => $row) {
            try {
                $filename = $row[SignalementImportImageHeader::COLUMN_FILENAME];
                $fileInfo = pathinfo($filename);
                if (4 !== \count($fileInfo)) {
                    continue;
                }
                if ($row[SignalementImportImageHeader::COLUMN_ID_ENREGISTREMENT] === $currentReference) {
                    $fileType = self::TYPE_IMAGE === $this->checkFileType($filename)
                        ? File::FILE_TYPE_PHOTO
                        : File::FILE_TYPE_DOCUMENT;

                    $files[] = $this->createFile($filename, $fileType);
                } else {
                    $signalement = $signalementRepository->findByReferenceChunk($territory, $currentReference);
                    if ($signalement instanceof Signalement) {
                        if (0 === $key % self::BATCH_SIZE) {
                            $this->signalementManager->flush();
                            $io->success(sprintf('%s flushed', self::BATCH_SIZE));
                        } else {
                            $signalement = $this->updateSignalement($signalement, $files);
                            $this->signalementManager->persist($signalement);
                            $io->success($signalement->getReference().' updated');
                            unset($signalement);
                            ++$countSignalement;
                        }
                    }
                    $fileType = self::TYPE_IMAGE === $this->checkFileType($filename)
                        ? File::FILE_TYPE_PHOTO
                        : File::FILE_TYPE_DOCUMENT;
                    $files[] = $this->createFile($filename, $fileType);
                    $currentReference = $row[SignalementImportImageHeader::COLUMN_ID_ENREGISTREMENT];
                }
            } catch (NonUniqueResultException $exception) {
                $this->logger->warning(
                    $row[SignalementImportImageHeader::COLUMN_ID_ENREGISTREMENT].':(Reference:'.$currentReference.') '.$exception->getMessage());

                $currentReference = $row[SignalementImportImageHeader::COLUMN_ID_ENREGISTREMENT];
            }
        }
        if (!empty($files)) { // persist the last one
            try {
                $signalement = $signalementRepository->findByReferenceChunk($territory, $currentReference);
                if ($signalement instanceof Signalement) {
                    $signalement = $this->updateSignalement($signalement, $files);
                    $io->success($signalement->getReference().' updated');
                    ++$countSignalement;
                    $this->signalementManager->persist($signalement);
                }
            } catch (NonUniqueResultException $exception) {
                $this->logger->warning(
                    $currentReference.':'.$exception->getMessage());
            }
        }

        $this->signalementManager->flush();
        $io->success(sprintf('%s Signalement(s) updated', $countSignalement));

        return Command::SUCCESS;
    }

    private function checkFileType(string $filePath): string
    {
        $fileInfo = pathinfo($filePath);
        if (preg_match(self::REGEX_IMAGE, $fileInfo['extension'])) {
            return self::TYPE_IMAGE;
        }

        return self::TYPE_DOCUMENT;
    }

    /**
     * @param File[] $files
     */
    private function updateSignalement(Signalement $signalement, array $files): Signalement
    {
        foreach ($files as $file) {
            $signalement->addFile($file);
        }

        return $signalement;
    }

    private function createFile(string $filename, string $fileType): File
    {
        return (new File())
            ->setFilename($filename)
            ->setTitle($filename)
            ->setFileType($fileType);
    }
}
