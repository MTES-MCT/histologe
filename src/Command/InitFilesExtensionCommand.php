<?php

namespace App\Command;

use App\Manager\HistoryEntryManager;
use App\Repository\FileRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:init-files-extension',
    description: 'Init files extension',
)]
class InitFilesExtensionCommand extends Command
{
    public const int BATCH_TOTAL_SIZE = 50000;
    public const int BATCH_FLUSH_SIZE = 5000;

    public function __construct(
        private readonly FileRepository $fileRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly HistoryEntryManager $historyEntryManager,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->historyEntryManager->removeEntityListeners();

        $io = new SymfonyStyle($input, $output);

        $files = $this->fileRepository->findBy(['extension' => null], ['createdAt' => 'DESC'], self::BATCH_TOTAL_SIZE);
        $i = 0;
        $total = \count($files);
        foreach ($files as $file) {
            ++$i;
            $ext = pathinfo($file->getFilename(), \PATHINFO_EXTENSION);
            $file->setExtension($ext);
            $io->info('File "'.$i.'/'.$total.'": '.$file->getFilename().' extension has been set with success');
            if (0 === $i % self::BATCH_FLUSH_SIZE) {
                $this->entityManager->flush();
            }
        }
        $this->entityManager->flush();

        return Command::SUCCESS;
    }
}
