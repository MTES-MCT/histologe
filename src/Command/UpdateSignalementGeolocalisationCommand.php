<?php

namespace App\Command;

use App\Entity\Signalement;
use App\Manager\HistoryEntryManager;
use App\Repository\SignalementRepository;
use App\Repository\TerritoryRepository;
use App\Service\Signalement\SignalementAddressUpdater;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:update-signalement-geolocalisation',
    description: 'Recompute geolocalisation signalement data for missing code insee signalement',
)]
class UpdateSignalementGeolocalisationCommand extends Command
{
    public const int BATCH_SIZE = 20;

    public function __construct(
        private readonly TerritoryRepository $territoryRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly SignalementAddressUpdater $signalementAddressUpdater,
        private readonly HistoryEntryManager $historyEntryManager,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('zip', null, InputOption::VALUE_OPTIONAL, 'Territory zip to target')
            ->addOption('split', null, InputOption::VALUE_OPTIONAL, 'Split signalements for prevent memory limit', 0)
            ->addOption('limit', null, InputOption::VALUE_OPTIONAL, 'full or partial')
            ->addOption('uuid', null, InputOption::VALUE_OPTIONAL, 'UUID du signalement')
            ->addOption('from_created_at', null, InputOption::VALUE_OPTIONAL, 'Get signalements data from created_at to 1 month');
    }

    /**
     * @throws \DateMalformedStringException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $zip = $input->getOption('zip');
        $split = $input->getOption('split');
        $uuid = $input->getOption('uuid');
        $fromCreatedAt = $input->getOption('from_created_at');
        $toCreatedAt = null;
        $signalements = null;

        /** @var SignalementRepository $signalementRepository */
        $signalementRepository = $this->entityManager->getRepository(Signalement::class);
        if ($uuid) {
            $signalements = $signalementRepository->findBy(['uuid' => $uuid]);
        } elseif (!empty($zip)) {
            $this->historyEntryManager->removeEntityListeners();
            $territory = $this->territoryRepository->findOneBy(['zip' => $zip]);
            $signalements = $signalementRepository->findSignalementsSplittedCreatedBefore($split, $territory);
        } elseif (!empty($fromCreatedAt)) {
            $fromCreatedAt = \DateTimeImmutable::createFromFormat('Y-m-d', $fromCreatedAt);
            if (false !== $fromCreatedAt) {
                $toCreatedAt = $fromCreatedAt->modify('+1 month');
                $signalements = $signalementRepository->findSignalementsBetweenDates(
                    $fromCreatedAt,
                    $toCreatedAt,
                );
                $io->note(\sprintf('Update signalements from %s to %s',
                    $fromCreatedAt->format('Y-m-d'),
                    $toCreatedAt->format('Y-m-d'))
                );
            }
        }

        if (empty($signalements)) {
            $io->warning('No address signalement to compute with BAN API');

            return Command::SUCCESS;
        }

        $i = 0;
        $progressBar = new ProgressBar($output, \count($signalements));
        $progressBar->start();
        /** @var Signalement $signalement */
        foreach ($signalements as $signalement) {
            $this->signalementAddressUpdater->updateAddressOccupantFromBanData($signalement);
            if (0 === $i % self::BATCH_SIZE) {
                $this->entityManager->flush();
            }
            ++$i;
            $progressBar->advance();
        }
        $this->entityManager->flush();
        $progressBar->finish();
        $io->newLine();
        $io->success(sprintf('%s signalements updated', $i));

        return Command::SUCCESS;
    }
}
