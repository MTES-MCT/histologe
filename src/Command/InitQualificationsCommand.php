<?php

namespace App\Command;

use App\Entity\Signalement;
use App\Manager\SignalementManager;
use App\Repository\SignalementRepository;
use App\Service\Signalement\Qualification\SignalementQualificationUpdater;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:init-qualifications',
    description: 'Initializes qualifications for existing signalements',
)]
class InitQualificationsCommand extends Command
{
    private const FLUSH_COUNT = 1000;

    public function __construct(
        private SignalementManager $signalementManager,
        private SignalementQualificationUpdater $signalementQualificationUpdater
        ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // ini_set("memory_limit", "-1"); // Hack for local env: uncomment this line if you have memory limit error

        $totalRead = 0;
        $io = new SymfonyStyle($input, $output);

        /** @var SignalementRepository $signalementRepository */
        $signalementRepository = $this->signalementManager->getRepository();
        $signalements = $signalementRepository->findAll();

        $progressBar = new ProgressBar($output, \count($signalements));
        $progressBar->start();

        /** @var Signalement $signalement */
        foreach ($signalements as $signalement) {
            ++$totalRead;
            $progressBar->advance();
            $this->signalementQualificationUpdater->updateQualificationFromScore($signalement);

            if (0 === $totalRead % self::FLUSH_COUNT) {
                $this->signalementManager->save($signalement);
            } else {
                $this->signalementManager->save($signalement, false);
            }
        }

        $this->signalementManager->flush();

        $progressBar->finish();
        $io->success(sprintf('%s signalements updated', $totalRead));

        return Command::SUCCESS;
    }
}
