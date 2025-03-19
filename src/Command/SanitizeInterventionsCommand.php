<?php

namespace App\Command;

use App\Manager\HistoryEntryManager;
use App\Repository\InterventionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerInterface;

#[AsCommand(
    name: 'app:sanitize-interventions',
    description: 'Sanitize interventions',
)]
class SanitizeInterventionsCommand extends Command
{
    private const int BATCH_SIZE = 1000;

    public function __construct(
        private readonly InterventionRepository $interventionRepository,
        private readonly EntityManagerInterface $entityManager,
        #[Autowire(service: 'html_sanitizer.sanitizer.app.message_sanitizer')]
        private readonly HtmlSanitizerInterface $htmlSanitizer,
        private readonly HistoryEntryManager $historyEntryManager,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->historyEntryManager->removeEntityListeners();
        $io = new SymfonyStyle($input, $output);
        $countAll = $this->interventionRepository->count([]);
        $io->info(sprintf('Found %s intervention to sanitize', $countAll));
        $interventions = $this->interventionRepository->getInterventionsWithDescription();
        $i = 0;
        $progressBar = new ProgressBar($output, \count($interventions));
        $progressBar->start();
        foreach ($interventions as $intervention) {
            $intervention->setDetails($this->htmlSanitizer->sanitize($intervention->getDetails(false)));
            ++$i;
            $progressBar->advance();
            if (0 === $i % self::BATCH_SIZE) {
                $this->entityManager->flush();
            }
        }
        $this->entityManager->flush();
        $progressBar->finish();
        $io->newLine();
        $io->success(sprintf('Sanitized %s/%s interventions', $i, $countAll));

        return Command::SUCCESS;
    }
}
