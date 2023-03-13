<?php

namespace App\Command;

use App\Entity\Signalement;
use App\Entity\Suivi;
use App\Repository\SignalementRepository;
use App\Service\Signalement\SuiviHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:update-signalement-lastsuivi',
    description: 'Add a short description for your command',
)]
class UpdateSignalementLastSuiviAtCommand extends Command
{
    private const FLUSH_COUNT = 5000;

    public function __construct(
        private SignalementRepository $signalementRepository,
        private EntityManagerInterface $entityManager,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $queryBuilder = $this->signalementRepository->createQueryBuilderActiveSignalement(removeArchived: true);
        $progressBar = new ProgressBar($output);
        $progressBar->start($this->signalementRepository->countAll(null, false, true));
        $count = 0;
        /** @var Signalement $signalement */
        foreach ($queryBuilder->getQuery()->toIterable() as $signalement) {
            $suivi = $signalement->getLastSuivi();
            if ($suivi instanceof Suivi) {
                ++$count;
                $signalement->setLastSuiviAt($suivi->getCreatedAt());
                $signalement->setLastSuiviBy(SuiviHelper::getSuiviLastByLabel($signalement));
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

        $io->success(sprintf('Data signalement_analytics created/updated with %s items', $count));

        return Command::SUCCESS;
    }
}
