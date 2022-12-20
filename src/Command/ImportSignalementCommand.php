<?php

namespace App\Command;

use App\Manager\SignalementManager;
use App\Repository\TerritoryRepository;
use App\Service\Parser\CsvParser;
use App\Service\Signalement\Import\SignalementImportMapper;
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
        private CsvParser $csvParser,
        private SignalementImportMapper $signalementImportMapper,
        private SignalementManager $signalementManager,
        private ParameterBagInterface $parameterBag,
        private TerritoryRepository $territoryRepository,
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
        $toFile = $this->parameterBag->get('uploads_tmp_dir').'signalement_'.$territoryZip.'.csv';
        $territory = $this->territoryRepository->findOneBy(['zip' => $territoryZip]);

        if (null === $territory) {
            $io->error('Territory does not exists');

            return Command::FAILURE;
        }

        $headers = $this->csvParser->getHeaders($toFile);
        $data = $this->csvParser->parseAsDict($toFile);
        $countSignalement = 0;
        foreach ($data as $item) {
            $dataMapped = $this->signalementImportMapper->map($headers, $item);
            if (!empty($dataMapped)) {
                ++$countSignalement;
                $signalement = $this->signalementManager->createOrGet($territory, $dataMapped);
                $signalement->setIsImported(true);
                $this->signalementManager->persist($signalement);
                $io->writeln(sprintf('%s added', $signalement->getReference()));
            }
        }
        $this->signalementManager->flush();
        $io->success(sprintf('%s have been imported', $countSignalement));

        return Command::SUCCESS;
    }
}
