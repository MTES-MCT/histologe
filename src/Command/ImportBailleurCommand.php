<?php

namespace App\Command;

use App\Service\Import\Bailleur\BailleurLoader;
use App\Service\Import\CsvParser;
use App\Service\UploadHandlerService;
use League\Flysystem\FilesystemOperator;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

#[AsCommand(
    name: 'app:import-bailleur',
    description: 'Import Bailleur from csv',
)]
class ImportBailleurCommand extends Command
{
    public function __construct(
        private CsvParser $csvParser,
        private BailleurLoader $bailleurLoader,
        private UploadHandlerService $uploadHandlerService,
        private FilesystemOperator $fileStorage,
        private ParameterBagInterface $parameterBag,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $fromFile = 'csv/bailleurs.csv';
        $toFile = $this->parameterBag->get('uploads_tmp_dir').'bailleurs.csv';
        if (!$this->fileStorage->fileExists($fromFile)) {
            $io->error('CSV File does not exists');

            return Command::FAILURE;
        }

        $this->uploadHandlerService->createTmpFileFromBucket($fromFile, $toFile);

        $this->bailleurLoader->load(
            $this->csvParser->parseAsDict($toFile),
            $output
        );

        $metadata = $this->bailleurLoader->getMetadata();
        foreach ($metadata['errors'] as $key => $error) {
            $io->warning($error);
        }

        $io->success(sprintf('%s bailleur(s) have been imported.', $metadata['count_bailleurs']));

        return Command::SUCCESS;
    }
}
