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
    public const PREFIX_FILENAME_STORAGE = 'mapping_doc_signalement_slugged_';
    public const BASE_DIRECTORY_CSV = 'csv/';
    private ?Territory $territory = null;
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

        if ($this->hasMissingColumnLabel($io)) {
            return Command::FAILURE;
        }

        $rows = $this->csvParser->parseAsDict($this->destinationFile);
        $filename = self::PREFIX_FILENAME_STORAGE.$this->territory->getZip().'.csv';

        $csvWriter = new CsvWriter(
            $tmpDirectory.$filename,
            $this->csvParser->getHeaders($this->destinationFile)
        );
        $countFileSlugged = 0;
        foreach ($rows as $index => $row) {
            $fileInfo = pathinfo($row[SignalementImportImageHeader::COLUMN_FILENAME]);
            $extension = $fileInfo['extension'] ?? null;
            if (null === $extension) {
                continue;
            }
            $filenameSlugged = $this->slugger->slug($fileInfo['filename'])->toString();
            if (\strlen($filenameSlugged) > 25) {
                $filenameSlugged = substr($filenameSlugged, 0, 25);
            }
            $filenameSlugged = uniqid().'-'.$filenameSlugged.'.'.$extension;

            try {
                $this->filesystem->rename(
                    $this->directoryPath.$row[SignalementImportImageHeader::COLUMN_FILENAME],
                    $this->directoryPath.$filenameSlugged,
                    true
                );
                $csvWriter->writeRow(
                    [
                        $row[SignalementImportImageHeader::COLUMN_ID_ENREGISTREMENT_ATTACHMENT],
                        $row[SignalementImportImageHeader::COLUMN_ID_ENREGISTREMENT],
                        $filenameSlugged,
                    ]
                );
                ++$countFileSlugged;
            } catch (\Throwable $exception) {
                $this->logger->error(sprintf('N° %s ligne avec %s', $index, $exception->getMessage()));
            }
        }

        $csvWriter->close();

        $file = file($tmpDirectory.$filename, \FILE_SKIP_EMPTY_LINES);

        $command = 'make upload action=image zip='.$this->territory->getZip();
        if (\count($file) > 1) {
            $this->uploadHandlerService->uploadFromFilename($filename, self::BASE_DIRECTORY_CSV);
            $io->success(sprintf('%s files has been slugify', $countFileSlugged));
            $io->success(
                sprintf(
                    '%s has been pushed to S3 bucket storage, please send your images to S3 Bucket `%s`',
                    $filename,
                    $command
                )
            );
        } else {
            $io->warning(sprintf('%s files has been slugify', $countFileSlugged));
            $io->warning(sprintf('%s is empty, please check if your images has been already slugged', $filename));
            $io->warning(sprintf('You should send your images to S3 Bucket with`%s`', $command));

            return Command::FAILURE;
        }

        return Command::SUCCESS;
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

        $fromFile = 'csv/mapping_doc_signalement_'.$zip.'.csv';
        $toFile = $this->parameterBag->get('uploads_tmp_dir').'mapping_doc_signalement_'.$zip.'.csv';

        /** @var Territory $territory */
        $territory = $this->territoryManager->findOneBy(['zip' => $zip]);
        if (null === $territory) {
            $this->errors[] = 'Territory does not exists';
        }

        $directoryPath = $this->projectDir.'/data/images/import_'.$zip.'/';
        if ($this->filesystem->exists($directoryPath)) {
            $countFile = \count(scandir($directoryPath)) - 2; // ignore single dot (.) and double dots (..)
            $question = new ConfirmationQuestion(
                sprintf('Do you want to slugify %s files from your directory %s ? ', $countFile, $directoryPath),
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
