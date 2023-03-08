<?php

namespace App\Command;

use App\Entity\SignalementAnalytics;
use App\Entity\Suivi;
use App\Repository\SignalementAnalyticsRepository;
use App\Repository\SignalementRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:create-signalement-analytics',
    description: 'Add a short description for your command',
)]
class CreateSignalementAnalyticsCommand extends Command
{
    private const FLUSH_COUNT = 500;

    public function __construct(
        private SignalementRepository $signalementRepository,
        private SignalementAnalyticsRepository $signalementAnalyticsRepository,
        private EntityManagerInterface $entityManager,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $queryBuilder = $this->signalementRepository->createQueryBuilderActiveSignalement(
            null,
            true,
            true
        );
        $progressBar = new ProgressBar($output);
        $progressBar->start($this->signalementRepository->countAll(null, true, true));
        $count = 0;
        foreach ($queryBuilder->getQuery()->toIterable() as $signalement) {
            $suivi = $signalement->getLastSuivi();
            if ($suivi instanceof Suivi) {
                ++$count;
                $signalementAnalytics = $this->signalementAnalyticsRepository->findOneBy(['signalement' => $signalement]);
                if (null !== $signalementAnalytics) {
                    $signalementAnalytics
                        ->setLastSuiviUserBy($suivi->getCreatedBy())
                        ->setLastSuiviAt($suivi->getCreatedAt());
                } else {
                    $signalementAnalytics = (new SignalementAnalytics())
                        ->setSignalement($signalement)
                        ->setLastSuiviUserBy($suivi->getCreatedBy())
                        ->setLastSuiviAt($suivi->getCreatedAt());
                }
                $this->entityManager->persist($signalementAnalytics);

                if (0 === $count % self::FLUSH_COUNT) {
                    $this->entityManager->persist($signalementAnalytics);
                    $this->entityManager->flush();
                }

                unset($signalementAnalytics);
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->entityManager->flush();

        $io->success(sprintf('Data signalement_analytics created/updated with %s items', $count));

        return Command::SUCCESS;
    }
}
