<?php

namespace App\Command;

use App\Entity\Territory;
use App\EventListener\ActivityListener;
use App\Service\Parser\CsvParser;
use App\Service\Signalement\Import\SignalementImportLoader;
use App\Service\UploadHandlerService;
use Doctrine\ORM\EntityManagerInterface;
use League\Flysystem\FilesystemOperator;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

#[AsCommand(
    name: 'app:import-signalement',
    description: 'Import signalement on storage S3',
)]
class ImportSignalementCommand extends Command
{
    public function __construct(
        private ActivityListener $activityListener,
        private CsvParser $csvParser,
        private ParameterBagInterface $parameterBag,
        private EntityManagerInterface $entityManager,
        private FilesystemOperator $fileStorage,
        private UploadHandlerService $uploadHandlerService,
        private SignalementImportLoader $signalementImportLoader,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('territory_zip', InputArgument::REQUIRED, 'Territory zip to target');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $territoryZip = $input->getArgument('territory_zip');
        $territory = $this->entityManager->getRepository(Territory::class)->findOneBy(['zip' => $territoryZip]);
        if (null === $territory) {
            $io->error('Territory does not exists');

            return Command::FAILURE;
        }

        $this->entityManager->getEventManager()->removeEventSubscriber($this->activityListener);

        $fromFile = 'csv/signalement_'.$territoryZip.'.csv';
        $toFile = $this->parameterBag->get('uploads_tmp_dir').'signalement.csv';
        if (!$this->fileStorage->fileExists($fromFile)) {
            $io->error('CSV File does not exists');

            return Command::FAILURE;
        }

        $this->uploadHandlerService->createTmpFileFromBucket($fromFile, $toFile);

        $this->signalementImportLoader->load(
            $territory,
            $this->csvParser->parseAsDict($toFile),
            $this->csvParser->getHeaders($toFile)
        );

        $metadata = $this->signalementImportLoader->getMetadata();

        $io->success(sprintf('%s have been imported', $metadata['count_signalement']));

        $this->entityManager->getEventManager()->addEventSubscriber($this->activityListener);

        return Command::SUCCESS;
    }
}
