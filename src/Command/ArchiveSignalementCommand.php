<?php

namespace App\Command;

use App\Entity\Enum\SignalementStatus;
use App\Manager\SignalementManager;
use App\Repository\SignalementRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:archive-signalement',
    description: 'Archive un signalement (équivalent de la suppression depuis le BO)',
)]
class ArchiveSignalementCommand extends Command
{
    public function __construct(
        private readonly SignalementRepository $signalementRepository,
        private readonly SignalementManager $signalementManager,
        private readonly EntityManagerInterface $entityManager,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('uuid', InputArgument::REQUIRED, 'Signalement uuid')
            ->addArgument('zip', InputArgument::REQUIRED, 'Code du territoire du signalement (ex: 13, 2A)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $uuid = $input->getArgument('uuid');
        $zip = $input->getArgument('zip');

        $signalement = $this->signalementRepository->findOneBy(['uuid' => $uuid]);
        if (null === $signalement) {
            $io->error(\sprintf('Aucun signalement trouvé avec l\'uuid %s.', $uuid));

            return Command::FAILURE;
        }

        $territory = $signalement->getAddress()->getTerritory();
        if (null === $territory || $territory->getZip() !== $zip) {
            $io->error(\sprintf(
                'Le signalement %s n\'appartient pas au territoire %s (territoire : %s).',
                $uuid,
                $zip,
                $territory?->getZip() ?? 'aucun'
            ));

            return Command::FAILURE;
        }

        if (!\in_array($signalement->getStatut(), [SignalementStatus::CLOSED, SignalementStatus::REFUSED])) {
            $io->error(\sprintf(
                'Le signalement #%s est au statut %s : seuls les signalements CLOSED ou REFUSED peuvent être archivés.',
                $signalement->getReference(),
                $signalement->getStatut()->value
            ));

            return Command::FAILURE;
        }

        if (!$io->confirm(\sprintf(
            'Archiver le signalement #%s (statut %s) du territoire %s ?',
            $signalement->getReference(),
            $signalement->getStatut()->value,
            $territory->getZipAndName()
        ), false)) {
            $io->note('Opération annulée.');

            return Command::SUCCESS;
        }

        $this->signalementManager->archive($signalement);
        $this->entityManager->flush();

        $io->success(\sprintf('Le signalement #%s a bien été archivé.', $signalement->getReference()));

        return Command::SUCCESS;
    }
}
