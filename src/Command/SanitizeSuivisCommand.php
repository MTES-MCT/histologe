<?php

namespace App\Command;

use App\Manager\HistoryEntryManager;
use App\Repository\SuiviRepository;
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
    name: 'app:sanitize-suivis',
    description: 'Sanitize suivis',
)]
class SanitizeSuivisCommand extends Command
{
    private const int BATCH_SIZE = 1000;

    public function __construct(
        private readonly SuiviRepository $suiviRepository,
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
        $countAll = $this->suiviRepository->count(['isSanitized' => false]);
        $io->info(sprintf('Found %s suivis to sanitize', $countAll));
        $suivis = $this->suiviRepository->findBy(['isSanitized' => false], ['createdAt' => 'DESC'], 50000);
        $i = 0;
        $progressBar = new ProgressBar($output, \count($suivis));
        $progressBar->start();
        foreach ($suivis as $suivi) {
            $suivi->setDescription($this->htmlSanitizer->sanitize($suivi->getDescription(false, false)));
            $suivi->setIsSanitized(true);
            ++$i;
            $progressBar->advance();
            if (0 === $i % self::BATCH_SIZE) {
                $this->entityManager->flush();
            }
        }
        $this->entityManager->flush();
        $progressBar->finish();
        $io->newLine();
        $io->success(sprintf('Sanitized %s/%s suivis', $i, $countAll));

        return Command::SUCCESS;
    }
}
