<?php

namespace App\Command;

use App\Entity\File;
use App\Entity\Territory;
use App\Manager\FileManager;
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
        private readonly TerritoryManager $territoryManager,
        private readonly SignalementManager $signalementManager,
        private readonly CsvParser $csvParser,
        private readonly ParameterBagInterface $parameterBag,
        private readonly FilesystemOperator $fileStorage,
        private readonly UploadHandlerService $uploadHandlerService,
        private readonly FileManager $fileManager,
        private readonly LoggerInterface $logger,
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

        $fromFile = 'csv/'.SlugifyDocumentSignalementCommand::PREFIX_FILENAME_STORAGE_MAPPING_SLUGGED.$zip.'.csv';
        $toFile = $this->parameterBag->get('uploads_tmp_dir').'mapping_doc_signalement_'.$zip.'.csv';
        if (!$this->fileStorage->fileExists($fromFile)) {
            $io->error('CSV Mapping file '.$fromFile.' does not exist, please execute app:slugify-doc-signalement');

            return Command::FAILURE;
        }

        $this->uploadHandlerService->createTmpFileFromBucket($fromFile, $toFile);

        $rows = $this->csvParser->parseAsDict($toFile);

        /** @var SignalementRepository $signalementRepository */
        $signalementRepository = $this->signalementManager->getRepository();
        $currentReference = $rows[0][SignalementImportImageHeader::COLUMN_ID_ENREGISTREMENT];
        $countFile = 0;
        foreach ($rows as $key => $row) {
            try {
                $filename = $row[SignalementImportImageHeader::COLUMN_FILENAME] ?? null;
                $fileInfo = pathinfo($filename);
                if (4 !== \count($fileInfo)) {
                    continue;
                }
                if (null !== $filename) {
                    $currentReference = $row[SignalementImportImageHeader::COLUMN_ID_ENREGISTREMENT];
                    $fileType = self::TYPE_IMAGE === $this->checkFileType($filename)
                        ? File::FILE_TYPE_PHOTO
                        : File::FILE_TYPE_DOCUMENT;

                    $signalement = $signalementRepository->findByReferenceChunk($territory, $currentReference);
                    if (null !== $signalement) {
                        $file = $this->fileManager->createOrUpdate(
                            filename: $filename,
                            title: $filename,
                            type: $fileType,
                            signalement: $signalement,
                        );
                        unset($file);
                        unset($signalement);
                        if (0 === $key % self::BATCH_SIZE) {
                            $this->fileManager->flush();
                        }
                        ++$countFile;
                    }
                }
            } catch (NonUniqueResultException $exception) {
                $this->logger->warning(
                    $row[SignalementImportImageHeader::COLUMN_ID_ENREGISTREMENT]
                    .':(Reference:'
                    .$currentReference
                    .') '
                    .$exception->getMessage());
            }
        }

        $this->fileManager->flush();
        $io->success(sprintf('%s files signalement(s) updated', $countFile));

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
}
