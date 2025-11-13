<?php

namespace App\Command;

use App\Manager\HistoryEntryManager;
use App\Repository\SuiviRepository;
use App\Repository\TerritoryRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:init-partner-in-suivi',
    description: 'Initialize partner in suivi entities based on user',
)]
class InitPartnerInSuiviCommand
{
    private const int BATCH_SIZE = 1000;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly SuiviRepository $suiviRepository,
        private readonly UserRepository $userRepository,
        private readonly TerritoryRepository $territoryRepository,
        private readonly HistoryEntryManager $historyEntryManager,
    ) {
        $this->historyEntryManager->removeEntityListeners();
    }

    public function __invoke(SymfonyStyle $io): int
    {
        $territories = $this->territoryRepository->findAllList();

        /** @var int $total */
        $total = $this->suiviRepository->findAllWithoutPartner(true);
        /** @var array<int, array<string, int>> $list */
        $list = $this->suiviRepository->findAllWithoutPartner();
        $progressBar = $io->createProgressBar(count($list));
        $progressBar->start();
        $i = 0;
        foreach ($list as $item) {
            $user = $this->userRepository->find($item['user_id']);
            $suivi = $this->suiviRepository->find($item['id']);
            $partner = $user->getPartnerInTerritoryOrFirstOne($territories[$item['territory_id']]);
            $suivi->setPartner($partner);
            $progressBar->advance();
            ++$i;
            if (($i % self::BATCH_SIZE) === 0) {
                $this->entityManager->flush();
            }
        }

        $this->entityManager->flush();
        $progressBar->finish();
        $io->success($i.' suivis mis à jour avec un partenaire sur '.$total.'.');

        return Command::SUCCESS;
    }
}
