<?php

namespace App\Command\Cron;

use App\Repository\FileRepository;
use App\Service\Mailer\NotificationMail;
use App\Service\Mailer\NotificationMailerRegistry;
use App\Service\Mailer\NotificationMailerType;
use Doctrine\ORM\EntityManagerInterface;
use League\Flysystem\FilesystemOperator;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

#[AsCommand(
    name: 'app:clear-storage-original-file',
    description: 'Clear original files from object storage S3 when resized files exist',
)]
class ClearStorageOriginalFileCommand extends AbstractCronCommand
{
    private SymfonyStyle $io;

    public function __construct(
        private readonly ParameterBagInterface $parameterBag,
        private readonly FileRepository $fileRepository,
        private readonly FilesystemOperator $fileStorage,
        private readonly EntityManagerInterface $entityManager,
        private readonly NotificationMailerRegistry $notificationMailerRegistry,
    ) {
        parent::__construct($this->parameterBag);
    }

    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        $this->io = new SymfonyStyle($input, $output);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $count = $this->fileRepository->findWithOriginalAndVariants(count : true);
        $files = $this->fileRepository->findWithOriginalAndVariants();
        $nbFilesToProcess = count($files);
        $progressBar = new ProgressBar($output, $nbFilesToProcess);
        $progressBar->start();
        $this->io->info('Found '.$count.' files with original and variants, process '.$nbFilesToProcess.' files');
        $nbErrors = 0;
        $nbTmp = 0;
        foreach ($files as $file) {
            $filename = $file->getFilename();
            if ($this->fileStorage->fileExists($filename)) {
                $this->fileStorage->delete($filename);
                ++$nbTmp;
            } else {
                ++$nbErrors;
            }
            $file->setIsOriginalDeleted(true);
            if ($nbTmp > 50) {
                $this->entityManager->flush();
                $nbTmp = 0;
            }
            $progressBar->advance();
        }
        $this->entityManager->flush();
        $progressBar->finish();
        $this->io->newLine();
        $this->io->success($nbFilesToProcess.' files processed with '.$nbErrors.' files not found');

        $this->notificationMailerRegistry->send(
            new NotificationMail(
                type: NotificationMailerType::TYPE_CRON,
                to: $this->parameterBag->get('admin_email'),
                message: sprintf('fichiers ont été traités dont %s fichiers introuvables sur un total de %s', $nbErrors, $count),
                cronLabel: 'Suppression de fichier(s) originaux s3://',
                cronCount: $nbFilesToProcess,
            )
        );

        return Command::SUCCESS;
    }
}
