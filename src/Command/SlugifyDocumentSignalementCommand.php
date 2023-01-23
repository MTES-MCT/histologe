<?php

namespace App\Command;

use App\Entity\Territory;
use App\Manager\TerritoryManager;
use App\Service\Import\CsvParser;
use App\Service\Import\CsvWriter;
use App\Service\UploadHandlerService;
use League\Flysystem\FilesystemException;
use League\Flysystem\FilesystemOperator;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
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
    private const PREFIX_FILENAME_STORAGE = 'mapping_doc_signalement_slugged_';
    private ?Territory $territory = null;
    private ?string $directoryPath = null;
    private ?string $sourceFile = null;
    private ?string $destinationFile = null;

    private ?string $error = null;

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

        if (!$this->validate($io, $input, $output)) {
            if ($this->error) {
                $io->error($this->error);
            }
            $io->writeln('Bye!');

            return Command::FAILURE;
        }

        $this->uploadHandlerService->createTmpFileFromBucket($this->sourceFile, $this->destinationFile);
        $rows = $this->csvParser->parseAsDict($this->destinationFile);
        $filename = self::PREFIX_FILENAME_STORAGE.$this->territory->getZip().'.csv';

        $csvWriter = new CsvWriter(
            'tmp/'.$filename,
            $this->csvParser->getHeaders($this->destinationFile)
        );
        $countFileSlugged = 0;
        foreach ($rows as $index => $row) {
            $fileInfo = pathinfo($row['sAttachFileName']);
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
                    $this->directoryPath.$row['sAttachFileName'],
                    $this->directoryPath.$filenameSlugged,
                    true
                );
                $csvWriter->writeRow([
                        $row['id_EnregistrementAttachment'],
                        $row['id_Enregistrement'],
                        $filenameSlugged,
                    ]
                );
                ++$countFileSlugged;
            } catch (\Throwable $exception) {
                $this->logger->error(sprintf('N° %s ligne avec %s', $index, $exception->getMessage()));
            }
        }

        $csvWriter->close();

        $this->uploadHandlerService->uploadFromFilename($filename);
        $io->success(sprintf('%s files has been slugify', $countFileSlugged));
        $io->success(
            sprintf('%s has been pushed to S3 bucket storage, please send your images to S3 Bucket', $filename)
        );

        return Command::SUCCESS;
    }

    /**
     * @throws FilesystemException
     */
    private function validate(SymfonyStyle $io, InputInterface $input, OutputInterface $output): bool
    {
        $helper = $this->getHelper('question');
        $question = new ConfirmationQuestion(
            'Did you send CSV mapping original file to S3 Bucket ? ',
            false
        );

        if (!$helper->ask($input, $output, $question)) {
            return false;
        }

        $zip = $input->getArgument('zip');
        $fromFile = 'csv/mapping_doc_signalement_'.$zip.'.csv';
        $toFile = $this->parameterBag->get('uploads_tmp_dir').'mapping_doc_signalement_'.$zip.'.csv';

        /** @var Territory $territory */
        $territory = $this->territoryManager->findOneBy(['zip' => $zip]);
        if (null === $territory) {
            $this->error = 'Territory does not exists';

            return false;
        }

        $directoryPath = $this->projectDir.'/data/images/import_'.$zip.'/';
        if (!$this->filesystem->exists($directoryPath)) {
            $this->error = sprintf('%s path directory does not exists', $directoryPath);

            return false;
        }

        $countFile = \count(scandir($directoryPath)) - 2; // ignore single dot (.) and double dots (..)
        $question = new ConfirmationQuestion(
            sprintf('Do you want to slugify %s files from your directory %s ? ', $countFile, $directoryPath),
            false
        );
        if (!$helper->ask($input, $output, $question)) {
            return false;
        }

        if (!$this->fileStorage->fileExists($fromFile)) {
            $this->error = 'CSV File does not exists';

            return false;
        }

        $this->sourceFile = $fromFile;
        $this->destinationFile = $toFile;
        $this->directoryPath = $directoryPath;
        $this->territory = $territory;

        return true;
    }
}
