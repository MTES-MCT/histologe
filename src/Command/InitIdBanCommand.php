<?php

namespace App\Command;

use App\Entity\Signalement;
use App\Manager\HistoryEntryManager;
use App\Repository\SignalementRepository;
use App\Service\Signalement\SignalementAddressUpdater;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:init-id-ban',
    description: 'Search BAN ID when missing in Adresse Occupant of Signalement',
)]
class InitIdBanCommand extends Command
{
    private const int BATCH_SIZE = 20;

    public function __construct(
        private readonly SignalementAddressUpdater $signalementAddressUpdater,
        private readonly SignalementRepository $signalementRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly HistoryEntryManager $historyEntryManager,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->historyEntryManager->removeEntityListeners();

        $io = new SymfonyStyle($input, $output);

        /** @var array<int, array{id: int}> $signalementIdsWithouBanId */
        $signalementIdsWithouBanId = $this->signalementRepository->findNullBanId();
        $nbSignalementWithoutBanId = \count($signalementIdsWithouBanId);

        $nb = 0;
        $progressBar = new ProgressBar($output, $nbSignalementWithoutBanId);
        $progressBar->start();

        $nbBatch = ceil($nbSignalementWithoutBanId / self::BATCH_SIZE);
        for ($i = 0; $i < $nbBatch; ++$i) {
            /** @var array<int, array{id: int}> $signalementsBatch */
            $signalementsBatch = array_splice($signalementIdsWithouBanId, 0, self::BATCH_SIZE);
            $signalementsIdsBatch = [];
            foreach ($signalementsBatch as $signalementBatch) {
                $signalementsIdsBatch[] = $signalementBatch['id'];
            }
            $listSignalementBanIdNull = $this->signalementRepository->findBy(['id' => $signalementsIdsBatch]);

            /** @var Signalement $signalement */
            foreach ($listSignalementBanIdNull as $signalement) {
                $this->signalementAddressUpdater->updateAddressOccupantFromBanData(
                    signalement: $signalement,
                    updateGeolocAndRnbId: false,
                );
                if (!empty($signalement->getBanIdOccupant())) {
                    ++$nb;
                }
                $progressBar->advance();
            }
            $this->entityManager->flush();
        }

        $progressBar->finish();
        $nbSignalementWithoutBanId = $this->signalementRepository->count(['banIdOccupant' => '0']);
        $io->success(\sprintf(
            '%s BAN IDs have been initialized, but %s signalements remain with no BAN ID',
            $nb,
            $nbSignalementWithoutBanId
        ));

        return Command::SUCCESS;
    }
}
