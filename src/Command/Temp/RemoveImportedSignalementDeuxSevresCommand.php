<?php

namespace App\Command\Temp;

use App\Entity\HistoryEntry;
use App\Entity\Signalement;
use App\Entity\Territory;
use App\Manager\HistoryEntryManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:remove-imported-signalement-deux-sevres',
    description: 'Remove imported signalements for Deux-Sèvres department',
)]
class RemoveImportedSignalementDeuxSevresCommand extends Command
{
    private const int TERRITORY_ID = 80;

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly HistoryEntryManager $historyEntryManager)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $this->historyEntryManager->removeEntityListeners();

        $historyEntryRepository = $this->em->getRepository(HistoryEntry::class);
        $territoryRepository = $this->em->getRepository(Territory::class);
        $territory = $territoryRepository->find(self::TERRITORY_ID);
        $signalementRepository = $this->em->getRepository(Signalement::class);
        $importedSignalements = $signalementRepository->findBy(['isImported' => true, 'territory' => $territory]);
        $io->info('Found '.count($importedSignalements).' imported signalements to remove.');

        foreach ($importedSignalements as $signalement) {
            $historyEntries = $historyEntryRepository->findBy(['signalement' => $signalement]);
            foreach ($historyEntries as $historyEntry) {
                $this->em->remove($historyEntry);
            }
            $this->em->remove($signalement);
        }
        $this->em->flush();

        $io->success('Removed '.count($importedSignalements).' imported signalements.');

        return Command::SUCCESS;
    }
}
