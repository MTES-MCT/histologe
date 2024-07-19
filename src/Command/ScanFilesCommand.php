<?php

namespace App\Command;

use App\Repository\FileRepository;
use App\Service\Security\FileScanner;
use App\Service\UploadHandlerService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:scan-files',
    description: 'Scan files',
)]
class ScanFilesCommand extends Command
{
    public function __construct(
        private readonly FileRepository $fileRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly FileScanner $fileScanner,
        private readonly UploadHandlerService $uploadHandlerService,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $files = $this->fileRepository->findBy(['scannedAt' => null], ['createdAt' => 'DESC'], 500);
        $i = 0;
        $total = \count($files);
        foreach ($files as $file) {
            ++$i;
            $filePath = $this->uploadHandlerService->getTmpFilepath($file->getFilename());
            if (!$filePath) {
                continue;
            }
            if (!$this->fileScanner->isClean($filePath, false)) {
                $io->error('File '.$file->getFilename().' ID '.$file->getId().' is infected');
                break;
            }
            $file->setScannedAt(new \DateTimeImmutable());
            $io->info('File "'.$i.'/'.$total.'" : '.$file->getFilename().' has been scanned with success');
            $this->entityManager->flush();
        }

        return Command::SUCCESS;
    }
}
