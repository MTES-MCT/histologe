<?php

namespace App\Command;

use App\Entity\Signalement;
use App\Manager\SignalementManager;
use App\Repository\CritereRepository;
use App\Repository\SignalementRepository;
use App\Service\Signalement\CriticiteCalculatorService;
use App\Service\Signalement\QualificationService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:calculate-new-criticite',
    description: 'Calculate new criticite score for all signalements',
)]
class CalculateNewCriticiteCommand extends Command
{
    private const FLUSH_COUNT = 1000;

    public function __construct(
        private SignalementManager $signalementManager,
        private CritereRepository $critereRepository,
        private QualificationService $qualificationService
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
        $signalements = $signalementRepository->findBy([
            'newScoreCreation' => 0,
        ]);

        $progressBar = new ProgressBar($output, \count($signalements));
        $progressBar->start();

        /** @var Signalement $signalement */
        foreach ($signalements as $signalement) {
            ++$totalRead;
            $progressBar->advance();
            $score = new CriticiteCalculatorService($signalement, $this->critereRepository);
            $signalement->setNewScoreCreation($score->calculateNewCriticite());
            $this->qualificationService->updateQualificationFromScore($signalement);

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
