<?php

namespace App\Command\Cron;

use App\Repository\FileRepository;
use App\Service\Mailer\NotificationMail;
use App\Service\Mailer\NotificationMailerRegistry;
use App\Service\Mailer\NotificationMailerType;
use League\Flysystem\FilesystemException;
use League\Flysystem\FilesystemOperator;
use League\Flysystem\StorageAttributes;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

#[AsCommand(
    name: 'app:clear-storage-tmp-folder',
    description: 'Clear tmp folder from object storage S3',
)]
class ClearStorageTmpFolderCommand extends AbstractCronCommand
{
    public function __construct(
        private readonly FilesystemOperator $fileStorage,
        private readonly ParameterBagInterface $parameterBag,
        private readonly NotificationMailerRegistry $notificationMailerRegistry,
        private readonly LoggerInterface $logger,
        private readonly FileRepository $fileRepository,
    ) {
        parent::__construct($this->parameterBag);
    }

    /**
     * @throws FilesystemException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $nbFiles = $this->cleanFilesOlderThan6Months($io, $output);
        $nbFiles = $this->cleanExportsOlderThan1Month($io, $output);

        $this->notificationMailerRegistry->send(
            new NotificationMail(
                type: NotificationMailerType::TYPE_CRON,
                to: $this->parameterBag->get('admin_email'),
                message: $nbFiles > 1
                    ? sprintf('%s fichiers ont été supprimés', $nbFiles)
                    : sprintf('%s a été supprimé', $nbFiles),
                cronLabel: 'Suppression de fichier(s) temporaires s3://tmp',
                cronCount: $nbFiles,
            )
        );

        return Command::SUCCESS;
    }

    private function cleanFilesOlderThan6Months(SymfonyStyle $io, OutputInterface $output): int
    {
        $datetime = (new \DateTimeImmutable('- 6 months'));
        $timestamp = $datetime->getTimestamp();

        $files = $this->fileStorage
            ->listContents('tmp/')
            ->filter(fn (StorageAttributes $attributes) => $attributes->lastModified() < $timestamp)
            ->toArray();

        $nbFiles = \count($files);

        $io->warning(sprintf(
            '%d fichier(s) déposé(s) avant le %s seront supprimés du repertoire tmp',
            $nbFiles,
            $datetime->format('Y-m-d H:i:s')
        ));

        $progressBar = new ProgressBar($output, $nbFiles);
        $progressBar->start();
        foreach ($files as $file) {
            if ('file' !== $file->type()) {
                continue;
            }
            $filePath = $file->path();
            $filePathDate = date('Y-m-d H:i:s', $file->lastModified());
            $this->fileStorage->delete($filePath);
            $this->logger->info(
                sprintf('Fichier supprimé : %s modifié le %s', $filePath, $filePathDate)
            );
            $progressBar->advance();
        }
        $progressBar->finish();
        $progressBar->clear();
        $io->success(sprintf('%d document(s) ont été supprimé(s) du repertoire /tmp', $nbFiles));

        return $nbFiles;
    }

    private function cleanExportsOlderThan1Month(SymfonyStyle $io, OutputInterface $output): int
    {
        $limit = new \DateTimeImmutable('- 1 month');
        $files = $this->fileRepository->findExportsOlderThan($limit);
        $nbFiles = \count($files);

        $io->warning(sprintf(
            '%d exports seront supprimés',
            $nbFiles,
        ));

        $progressBar = new ProgressBar($output, $nbFiles);
        $progressBar->start();
        foreach ($files as $file) {
            $filename = $file->getFilename();
            if ($this->fileStorage->fileExists($filename)) {
                $this->fileStorage->delete($filename);
                $this->logger->info(
                    sprintf('Fichier supprimé : %s', $filename)
                );
            }
            $progressBar->advance();
        }
        $progressBar->finish();
        $progressBar->clear();
        $io->success(sprintf('%d exports ont été supprimés du repertoire /tmp', $nbFiles));

        return $nbFiles;
    }
}
