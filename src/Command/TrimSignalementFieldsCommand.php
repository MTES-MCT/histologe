<?php

namespace App\Command;

use App\Repository\SignalementRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:trim-signalement-fields',
    description: 'Trim signalement fields'
)]
class TrimSignalementFieldsCommand extends Command
{
    private SymfonyStyle $io;

    public function __construct(
        private SignalementRepository $signalementRepository,
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
        $this->signalementRepository->trimFields();

        $this->io->success('Signalement fields were successfully fixed.');

        return Command::SUCCESS;
    }
}
