<?php

namespace App\Command;

use App\Entity\Signalement;
use App\Manager\SignalementManager;
use App\Repository\SignalementRepository;
use App\Service\Signalement\CriticiteCalculator;
use App\Service\Signalement\Qualification\SignalementQualificationUpdater;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:fix-score-qualifications',
    description: 'Fix scores and qualifications of signalements created between 14th of february and 8th of march'
)]
class FixScoreQualificationsCommand extends Command
{
    private SymfonyStyle $io;

    public function __construct(
        private SignalementRepository $signalementRepository,
        private SignalementManager $signalementManager,
        private CriticiteCalculator $criticiteCalculator,
        private SignalementQualificationUpdater $signalementQualificationUpdater,
    ) {
        parent::__construct();
    }

    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        // See https://symfony.com/doc/current/console/style.html
        $this->io = new SymfonyStyle($input, $output);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $count = 0;
        $countScoreChanged = 0;
        $countQualifChanged = 0;

        $startDate = new \DateTimeImmutable('2024-02-14');
        $endDate = new \DateTimeImmutable('2024-03-08');

        /** @var Signalement[] $signalements */
        $signalements = $this->signalementRepository->findSignalementsBetweenDates($startDate, $endDate);

        $this->io->info(\sprintf('%s signalements à parcourir.', \count($signalements)));

        /** @var Signalement $signalement */
        foreach ($signalements as $signalement) {
            $oldScore = $signalement->getScore();
            /** @var ArrayCollection $oldQualifications */
            $oldQualifications = new ArrayCollection($signalement->getSignalementQualifications()->toArray());

            $newScore = $this->criticiteCalculator->calculate($signalement);
            $signalement->setScore($newScore);
            $this->signalementQualificationUpdater->updateQualificationFromScore($signalement);
            $newQualifications = $signalement->getSignalementQualifications();
            $this->signalementManager->save($signalement, false);

            if (abs($oldScore - $newScore) > 0.00001) {
                $this->io->info(\sprintf('Score of signalement %s changed from %s to %s.',
                    $signalement->getUuid(), $oldScore, $newScore));
                ++$countScoreChanged;
            }
            if ($oldQualifications->count() !== $newQualifications->count()) {
                $this->io->info(\sprintf('Number of qualifications of signalement %s changed %s to %s.',
                    $signalement->getUuid(), $oldQualifications->count(), $newQualifications->count()));
                ++$countQualifChanged;
            }

            ++$count;
        }
        $this->signalementManager->flush();

        $this->io->success(\sprintf(
            '%s signalements were analyzed. %s scores changed. %s number of qualifications changed',
            $count,
            $countScoreChanged,
            $countQualifChanged
        ));

        return Command::SUCCESS;
    }
}
