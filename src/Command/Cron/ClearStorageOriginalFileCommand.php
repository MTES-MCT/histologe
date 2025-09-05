<?php

namespace App\Command\Cron;

use App\Repository\FileRepository;
use App\Service\Mailer\NotificationMail;
use App\Service\Mailer\NotificationMailerRegistry;
use App\Service\Mailer\NotificationMailerType;
use League\Flysystem\FilesystemException;
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
    private const int MAX_FILES_PROCESSED = 2500;
    private SymfonyStyle $io;

    public function __construct(
        private readonly ParameterBagInterface $parameterBag,
        private readonly FileRepository $fileRepository,
        private readonly FilesystemOperator $fileStorage,
        private readonly NotificationMailerRegistry $notificationMailerRegistry,
    ) {
        parent::__construct($this->parameterBag);
    }

    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        $this->io = new SymfonyStyle($input, $output);
    }

    /**
     * @throws FilesystemException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $limit = new \DateTimeImmutable('- 1 month');
        $count = $this->fileRepository->countWithOriginalAndVariants($limit);
        $files = $this->fileRepository->findWithOriginalAndVariants($limit, self::MAX_FILES_PROCESSED);
        $nbFilesToProcess = count($files);
        $this->io->info('Found '.$count.' files with original and variants, process '.$nbFilesToProcess.' files');
        $progressBar = new ProgressBar($output, $nbFilesToProcess);
        $progressBar->start();
        $nbErrors = 0;
        $ids = [];
        foreach ($files as $file) {
            $ids[] = $file->getId();
            $filename = $file->getFilename();
            if ($this->fileStorage->fileExists($filename)) {
                $this->fileStorage->delete($filename);
            } else {
                ++$nbErrors;
            }
            $progressBar->advance();
        }
        $this->fileRepository->updateWithOriginalAndVariants($ids);
        $progressBar->finish();
        $this->io->newLine();
        $this->io->success($nbFilesToProcess.' files processed with '.$nbErrors.' files not found');

        $this->notificationMailerRegistry->send(
            new NotificationMail(
                type: NotificationMailerType::TYPE_CRON,
                to: (string) $this->parameterBag->get('admin_email'),
                message: sprintf('fichiers ont été traités dont %s fichiers introuvables sur un total de %s', $nbErrors, $count),
                cronLabel: 'Suppression de fichier(s) originaux s3://',
                cronCount: $nbFilesToProcess,
            )
        );

        return Command::SUCCESS;
    }
}
