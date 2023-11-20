<?php

namespace App\Command;

use App\Entity\Territory;
use App\Manager\TerritoryManager;
use App\Service\Import\CsvParser;
use App\Service\Import\CsvWriter;
use App\Service\Import\Signalement\SignalementImportImageHeader;
use App\Service\UploadHandlerService;
use League\Flysystem\FilesystemException;
use League\Flysystem\FilesystemOperator;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\String\Slugger\SluggerInterface;

#[AsCommand(
    name: 'app:slugify-doc-signalement',
    description: 'Slugify file local document and csv mapping file, this command should be only executed in local',
)]
class SlugifyDocumentSignalementCommand extends Command
{
    public const PREFIX_FILENAME_STORAGE_MAPPING = 'mapping_doc_signalement_';
    public const PREFIX_FILENAME_STORAGE_MAPPING_SLUGGED = 'mapping_doc_signalement_slugged_';
    public const PREFIX_FILENAME_STORAGE_SIGNALEMENT = 'signalement_';
    public const PREFIX_FILENAME_STORAGE_SIGNALEMENT_SLUGGED = 'signalement_slugged_';
    public const BASE_DIRECTORY_CSV = 'csv/';
    public const IMPORT_SIGNALEMENT_COLUMN_PHOTOS = 'ref des photos';
    public const IMPORT_SIGNALEMENT_COLUMN_DOCUMENTS = 'ref des documents';

    private ?Territory $territory = null;
    private bool $isMappingFile;
    private ?string $filename = null;
    private ?string $directoryPath = null;
    private ?string $sourceFile = null;
    private ?string $destinationFile = null;
    private ?array $errors = [];

    public function __construct(
        private ParameterBagInterface $parameterBag,
        private SluggerInterface $slugger,
        private LoggerInterface $logger,
        private FilesystemOperator $fileStorage,
        private UploadHandlerService $uploadHandlerService,
        private TerritoryManager $territoryManager,
        private CsvParser $csvParser,
        private Filesystem $filesystem,
        private string $projectDir
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('zip', InputArgument::REQUIRED, 'Territory zip to target');
        $this->addArgument('mapping', InputArgument::REQUIRED, 'Is it a mapping or a list of signalements');
    }

    /**
     * @throws FilesystemException
     */
    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        if ('prod' === $this->parameterBag->get('kernel.environment')) {
            $io->warning('Watch out! This command is not allowed to be executed to production');

            return Command::FAILURE;
        }

        if (!$this->validate($input, $output)) {
            $io->error($this->errors);
            $io->writeln('Bye!');

            return Command::FAILURE;
        }

        $tmpDirectory = $this->parameterBag->get('uploads_tmp_dir');
        $this->uploadHandlerService->createTmpFileFromBucket($this->sourceFile, $this->destinationFile);

        if ($this->isMappingFile && $this->hasMissingColumnLabel($io)) {
            return Command::FAILURE;
        }

        $rows = $this->csvParser->parseAsDict($this->destinationFile);
        $filename = $this->isMappingFile
                        ? self::PREFIX_FILENAME_STORAGE_MAPPING_SLUGGED.$this->territory->getZip().'.csv'
                        : self::PREFIX_FILENAME_STORAGE_SIGNALEMENT_SLUGGED.$this->territory->getZip().'.csv';

        $csvWriter = new CsvWriter(
            $tmpDirectory.$filename,
            $this->csvParser->getHeaders($this->destinationFile)
        );
        $countFileSlugged = 0;
        foreach ($rows as $index => $row) {
            if ($this->isMappingFile) {
                if (!empty($row[SignalementImportImageHeader::COLUMN_FILENAME])) {
                    $rowFilename = $row[SignalementImportImageHeader::COLUMN_FILENAME];
                    if ($this->makeSlugForMappingFile($csvWriter, $rowFilename, $index, $row)) {
                        ++$countFileSlugged;
                    }
                }
            } else {
                if (!empty($row[self::IMPORT_SIGNALEMENT_COLUMN_PHOTOS])) {
                    $row[self::IMPORT_SIGNALEMENT_COLUMN_PHOTOS]
                        = $this->makeSlugsForSignalementFile(self::IMPORT_SIGNALEMENT_COLUMN_PHOTOS, $index, $row);
                    $countFileSlugged += \count(explode('|', $row[self::IMPORT_SIGNALEMENT_COLUMN_PHOTOS]));
                }
                if (!empty($row[self::IMPORT_SIGNALEMENT_COLUMN_DOCUMENTS])) {
                    $row[self::IMPORT_SIGNALEMENT_COLUMN_DOCUMENTS]
                        = $this->makeSlugsForSignalementFile(self::IMPORT_SIGNALEMENT_COLUMN_DOCUMENTS, $index, $row);
                    $countFileSlugged += \count(explode('|', $row[self::IMPORT_SIGNALEMENT_COLUMN_DOCUMENTS]));
                }
                try {
                    $csvWriter->writeRow($row);
                } catch (\Throwable $exception) {
                    $this->logger->error(sprintf('CSV Write - row %s - error: %s', $index, $exception->getMessage()));
                }
            }
        }

        $csvWriter->close();

        $file = file($tmpDirectory.$filename, \FILE_SKIP_EMPTY_LINES);

        $command = 'make upload action=image zip='.$this->territory->getZip();
        if (\count($file) > 1) {
            $this->uploadHandlerService->moveFromBucketTempFolder($filename, self::BASE_DIRECTORY_CSV);
            $io->success(sprintf('%s files have been slugified', $countFileSlugged));
            $io->success(
                sprintf(
                    '%s has been pushed to S3 bucket storage, please send your images to S3 Bucket `%s`',
                    $filename,
                    $command
                )
            );
        } else {
            $io->warning(sprintf('%s files have been slugified', $countFileSlugged));
            $io->warning(sprintf('%s is empty, please check if your images have been already slugged', $filename));
            $io->warning(sprintf('You should send your images to S3 Bucket with`%s`', $command));

            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }

    private function makeSlugForMappingFile(CsvWriter $csvWriter, $filename, int $index, $row): int
    {
        $filenameSlugged = !empty($filename) ? $this->getSluggedFile($filename, $index) : null;
        if (!empty($filenameSlugged)) {
            try {
                $csvWriter->writeRow(
                    [
                        $row[SignalementImportImageHeader::COLUMN_ID_ENREGISTREMENT_ATTACHMENT],
                        $row[SignalementImportImageHeader::COLUMN_ID_ENREGISTREMENT],
                        $filenameSlugged,
                    ]
                );

                return 1;
            } catch (\Throwable $exception) {
                $this->logger->error(sprintf('CSV Write - N° %s ligne avec %s', $index, $exception->getMessage()));
            }
        }

        return 0;
    }

    private function makeSlugsForSignalementFile(string $colName, $index, $row): ?string
    {
        $fileListSlugged = [];
        $fileList = explode('|', $row[$colName]);
        foreach ($fileList as $filename) {
            $filenameSlugged = !empty($filename) ? $this->getSluggedFile($filename, $index) : null;
            if (!empty($filenameSlugged)) {
                $fileListSlugged[] = $filenameSlugged;
            }
        }

        $countFileList = \count($fileList);
        $countFileSlugged = \count($fileListSlugged);
        if ($countFileList != $countFileSlugged) {
            $this->logger->error(sprintf('Different count - row %s col %s - %s // %s', $index, $colName, $countFileSlugged, $countFileList));

            return null;
        }

        return implode('|', $fileListSlugged);
    }

    private function getSluggedFile(string $filename, int $index): ?string
    {
        $fileInfo = pathinfo($filename);
        $extension = $fileInfo['extension'] ?? null;
        if (null === $extension) {
            return null;
        }
        $filenameSlugged = $this->slugger->slug($fileInfo['filename'])->toString();
        if (\strlen($filenameSlugged) > 25) {
            $filenameSlugged = substr($filenameSlugged, 0, 25);
        }
        $filenameSlugged = uniqid().'-'.$filenameSlugged.'.'.$extension;

        try {
            $this->filesystem->rename(
                $this->directoryPath.$filename,
                $this->directoryPath.$filenameSlugged,
                true
            );

            return $filenameSlugged;
        } catch (\Throwable $exception) {
            $this->logger->error(sprintf('File rename - row %s - error: %s', $index, $exception->getMessage()));
        }

        return null;
    }

    /**
     * @throws FilesystemException
     */
    private function validate(InputInterface $input, OutputInterface $output): bool
    {
        $zip = $input->getArgument('zip');
        /** @var QuestionHelper $helper */
        $helper = $this->getHelper('question');
        $question = new ConfirmationQuestion(
            'Did you send CSV mapping file to S3 Bucket ?',
            false
        );

        if (!$helper->ask($input, $output, $question)) {
            $this->errors[] = 'Please execute `make upload action=mapping-doc zip='.$zip.'`';

            return false;
        }

        $this->isMappingFile = '1' == $input->getArgument('mapping');
        $this->filename = $this->isMappingFile
                            ? self::PREFIX_FILENAME_STORAGE_MAPPING
                            : self::PREFIX_FILENAME_STORAGE_SIGNALEMENT;

        $fromFile = 'csv/'.$this->filename.$zip.'.csv';
        $toFile = $this->parameterBag->get('uploads_tmp_dir').$this->filename.$zip.'.csv';

        /** @var Territory $territory */
        $territory = $this->territoryManager->findOneBy(['zip' => $zip]);
        if (null === $territory) {
            $this->errors[] = 'Territory does not exists';
        }

        $directoryPath = $this->projectDir.'/data/images/import_'.$zip.'/';
        if ($this->filesystem->exists($directoryPath)) {
            $countFile = \count(scandir($directoryPath)) - 2; // ignore single dot (.) and double dots (..)
            $question = new ConfirmationQuestion(
                sprintf('Do you want to slugify %s files from your directory %s?', $countFile, $directoryPath),
                false
            );
            if (!$helper->ask($input, $output, $question)) {
                return false;
            }
        } else {
            $this->errors[] = sprintf('%s path directory does not exists', $directoryPath);
        }

        if (!$this->fileStorage->fileExists($fromFile)) {
            $this->errors[] = 'CSV File does not exists';
        }

        $this->sourceFile = $fromFile;
        $this->destinationFile = $toFile;
        $this->directoryPath = $directoryPath;
        $this->territory = $territory;

        return empty($this->errors);
    }

    private function hasMissingColumnLabel(SymfonyStyle $io): bool
    {
        $errors = [];
        $header = $this->csvParser->getHeaders($this->destinationFile);
        foreach (SignalementImportImageHeader::COLUMNS_LIST as $column) {
            if (!\in_array($column, $header)) {
                $errors[] = $column.' is missing.';
            }
        }

        if (!empty($errors)) {
            $io->error($errors);
        }

        return !empty($errors);
    }
}
