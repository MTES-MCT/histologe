<?php

namespace App\Command;

use App\Entity\Signalement;
use App\Entity\Suivi;
use App\Repository\SignalementRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:update-signalement-lastsuivi',
    description: 'Update signalement visibility',
)]
class UpdateSignalementLastSuiviCommand extends Command
{
    private const FLUSH_COUNT = 1000;

    public function __construct(
        private SignalementRepository $signalementRepository,
        private EntityManagerInterface $entityManager,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $queryBuilder = $this->signalementRepository->createQueryBuilderActiveSignalement();
        $progressBar = new ProgressBar($output);
        $progressBar->start($this->signalementRepository->countAll(
            territory: null,
            partner: null,
            removeArchived: true
        ));

        $count = 0;
        /** @var Signalement $signalement */
        foreach ($queryBuilder->getQuery()->toIterable() as $signalement) {
            $suivi = $signalement->getLastSuivi();
            if ($suivi instanceof Suivi) {
                ++$count;
                $signalement->setLastSuiviIsPublic($suivi->getIsPublic());
                $this->entityManager->persist($signalement);

                if (0 === $count % self::FLUSH_COUNT) {
                    $this->entityManager->persist($signalement);
                    $this->entityManager->flush();
                    $this->entityManager->clear();
                }
                unset($signalement);
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->entityManager->flush();
        $this->entityManager->clear();

        $io->success(sprintf('Data signalement created/updated with %s items', $count));

        return Command::SUCCESS;
    }
}
