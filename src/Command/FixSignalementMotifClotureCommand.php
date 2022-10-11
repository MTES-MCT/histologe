<?php

namespace App\Command;

use App\Entity\Signalement;
use App\Manager\SignalementManager;
use App\Manager\TerritoryManager;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Statement;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:fix-signalement-motif-cloture',
    description: 'Fix motoif cloture',
)]
class FixSignalementMotifClotureCommand extends Command
{
    public const LEGACY_TERRITORY = [
        '81', '08', '29', '69', '71', '63', '19',
    ];

    public function __construct(
        private ManagerRegistry $managerRegistry,
        private EntityManagerInterface $entityManager,
        private SignalementManager $signalementManager,
        private TerritoryManager $territoryManager)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('territory_zip', InputArgument::REQUIRED, 'The territory of legacy platform');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $territoryZip = $input->getArgument('territory_zip');
        if (!\in_array($territoryZip, self::LEGACY_TERRITORY)) {
            $io->error(sprintf('%s is not legacy territory', $territoryZip));

            return Command::FAILURE;
        }

        $io->info(sprintf('You passed an argument: %s', $territoryZip));
        $territory = $this->territoryManager->findOneBy(['zip' => $territoryZip]);
        $signalements = $this->signalementManager->findBy(['territory' => $territory, 'motifCloture' => 0]);

        /* @var Connection $connection */
        $connection = $this->managerRegistry->getConnection('legacy_'.$territoryZip);
        $connection->connect();

        /** @var Signalement $signalement */
        foreach ($signalements as $signalement) {
            /** @var Statement $statement */
            $statement = $connection->prepare('SELECT id, uuid, motif_cloture from signalement where uuid = :uuid');
            $legacySignalement = $statement->executeQuery(['uuid' => $signalement->getUuid()])->fetchAssociative();

            $signalement->setMotifCloture($legacySignalement['motif_cloture']);
            $this->signalementManager->save($signalement, false);
        }
        $this->signalementManager->flush();

        $io->success(sprintf('%s signalement(s) updated with bad motif cloture', \count($signalements)));

        return Command::SUCCESS;
    }
}
