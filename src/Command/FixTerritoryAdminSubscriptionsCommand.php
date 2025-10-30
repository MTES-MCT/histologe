<?php

namespace App\Command;

use App\Manager\UserManager;
use App\Manager\UserSignalementSubscriptionManager;
use App\Repository\SignalementRepository;
use App\Repository\UserRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:fix-territory-admin-subscriptions',
    description: 'Subscribe territory admins to signalements validated from 28/10/2025 where no territory admin is subscribed',
)]
class FixTerritoryAdminSubscriptionsCommand extends Command
{
    private const string START_DATE = '2025-10-28 00:00:00';

    public function __construct(
        private readonly SignalementRepository $signalementRepository,
        private readonly UserRepository $userRepository,
        private readonly UserManager $userManager,
        private readonly UserSignalementSubscriptionManager $subscriptionManager,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Fixing territory admin subscriptions for signalements validated from 28/10/2025');

        $adminUser = $this->userManager->getSystemUser();

        // Find all signalements validated from 28/10/2025
        $startDate = new \DateTimeImmutable(self::START_DATE);
        $signalements = $this->signalementRepository->createQueryBuilder('s')
            ->where('s.validatedAt >= :startDate')
            ->setParameter('startDate', $startDate)
            ->getQuery()
            ->getResult();

        $io->info(sprintf('Found %d signalements validated from 28/10/2025', \count($signalements)));

        if (empty($signalements)) {
            $io->success('No signalements to process');

            return Command::SUCCESS;
        }

        $progressBar = $io->createProgressBar(\count($signalements));
        $progressBar->start();

        $processedCount = 0;
        $subscribedCount = 0;

        foreach ($signalements as $signalement) {
            // Check if any territory admin is already subscribed to this signalement
            $subscribedTerritoryAdmins = $this->userRepository->findUsersSubscribedToSignalement($signalement, true);

            if (!empty($subscribedTerritoryAdmins)) {
                $progressBar->advance();
                continue;
            }

            $io->info('Subscription to signalement '.$signalement->getUuid());

            $territoryAdmins = $this->userRepository->findActiveTerritoryAdmins(
                territoryId: $signalement->getTerritory()->getId(),
            );

            foreach ($territoryAdmins as $territoryAdmin) {
                $this->subscriptionManager->createOrGet(
                    userToSubscribe: $territoryAdmin,
                    signalement: $signalement,
                    createdBy: $adminUser
                );
                ++$subscribedCount;
            }

            ++$processedCount;
            $progressBar->advance();
        }

        $this->subscriptionManager->flush();
        $progressBar->finish();
        $io->newLine(2);

        $io->success(sprintf(
            'Processed %d signalements and created %d subscriptions',
            $processedCount,
            $subscribedCount
        ));

        return Command::SUCCESS;
    }
}
