<?php

namespace App\Command;

use App\Service\Import\CsvParser;
use App\Service\Import\Desordres\DesordresImportLoader;
use App\Service\UploadHandlerService;
use League\Flysystem\FilesystemOperator;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

#[AsCommand(
    name: 'app:import-desordres-tables',
    description: 'Import DesordreCategorie, DesordreCritere, DesordrePrecision',
)]
class ImportDesordresTablesCommand extends Command
{
    public function __construct(
        private CsvParser $csvParser,
        private ParameterBagInterface $parameterBag,
        private FilesystemOperator $fileStorage,
        private UploadHandlerService $uploadHandlerService,
        private DesordresImportLoader $desordresImportLoader,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $fromFile = 'csv/desordres_tables.csv';
        $toFile = $this->parameterBag->get('uploads_tmp_dir').'desordres_tables.csv';
        if (!$this->fileStorage->fileExists($fromFile)) {
            $io->error('CSV File does not exists');

            return Command::FAILURE;
        }

        $this->uploadHandlerService->createTmpFileFromBucket($fromFile, $toFile);

        $this->desordresImportLoader->load(
            $this->csvParser->parseAsDict($toFile),
            $this->csvParser->getHeaders($toFile),
            $output
        );

        $metadata = $this->desordresImportLoader->getMetadata();

        $io->success(sprintf('%s desordre_categorie have been created', $metadata['count_desordre_categorie_created']));
        $io->success(sprintf('%s desordre_critere have been created', $metadata['count_desordre_critere_created']));
        $io->success(sprintf('%s desordre_precision have been created', $metadata['count_desordre_precision_created']));
        $io->success(sprintf('%s desordre_precision have been updated', $metadata['count_desordre_precision_updated']));

        return Command::SUCCESS;
    }
}
