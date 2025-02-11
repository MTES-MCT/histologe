<?php

namespace App\Command;

use App\Repository\PartnerRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:trim-partner-names',
    description: 'Trim partner names'
)]
class TrimPartnerNamesCommand extends Command
{
    public function __construct(
        private readonly PartnerRepository $partnerRepository,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->partnerRepository->trimPartnerNames();

        return Command::SUCCESS;
    }
}
