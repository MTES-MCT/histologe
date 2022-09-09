<?php

namespace App\Command;

use App\Entity\Territory;
use App\Manager\TerritoryManager;
use App\Service\GridAffectation\GridAffectationLoader;
use App\Service\Parser\CsvParser;
use League\Flysystem\FilesystemOperator;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

#[AsCommand(
    name: 'app:import-grid-affectation',
    description: 'Import grille affectation based on storage S3',
)]
class ImportGridAffectationCommand extends Command
{
    public function __construct(
        private FilesystemOperator $fileStorage,
        private ParameterBagInterface $parameterBag,
        private CsvParser $csvParser,
        private TerritoryManager $territoryManager,
        private GridAffectationLoader $gridAffectationLoader
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
        $fromFile = 'csv/grille_affectation_'.$territoryZip.'.csv';
        $toFile = $this->parameterBag->get('uploads_tmp_dir').'grille.csv';

        /** @var Territory $territory */
        $territory = $this->territoryManager->findOneBy(['zip' => $territoryZip]);
        if (null === $territory) {
            $io->error('Territory does not exists');

            return Command::FAILURE;
        }

        if ($territory->isIsActive()) {
            $io->warning('Partner(s) and user(s) from this repository has already been added');

            return Command::FAILURE;
        }

        if (!$this->fileStorage->fileExists($fromFile)) {
            $io->error('CSV File does not exists');

            return Command::FAILURE;
        }

        $this->createTmpFileFromBucket($fromFile, $toFile);
        $this->gridAffectationLoader->load($this->csvParser->parse($toFile), $territory);

        $metadata = $this->gridAffectationLoader->getMetadata();
        $io->success($metadata['nb_partners'].' partner(s) created, '.$metadata['nb_users'].' user(s) created');

        $territory->setIsActive(true);
        $this->territoryManager->save($territory);
        $io->success($territory->getName().' has been activated');

        return Command::SUCCESS;
    }

    private function createTmpFileFromBucket($from, $to): void
    {
        $resourceBucket = $this->fileStorage->read($from);
        $resourceFileSytem = fopen($to, 'w');
        fwrite($resourceFileSytem, $resourceBucket);
        fclose($resourceFileSytem);
    }
}
