<?php

namespace App\Command\Cron;

use League\Flysystem\FilesystemException;
use League\Flysystem\FilesystemOperator;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
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
        private readonly LoggerInterface $logger,
    ) {
        parent::__construct($this->parameterBag);
    }

    /**
     * @throws FilesystemException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $files = $this->fileStorage->listContents('tmp/');
        $datetime = (new \DateTimeImmutable('- 179 days'));
        $timestamp = $datetime->getTimestamp();
        $io->warning(sprintf(
            'Les fichiers modifiés avant le %s seront supprimés',
            $datetime->format('Y-m-d H:i:s'))
        );
        $countDeleted = 0;
        foreach ($files as $file) {
            if ('file' === $file['type']) {
                $filePath = $file['path'];
                $fileTimestamp = $this->fileStorage->lastModified($filePath);
                $filePathDate = date('Y-m-d H:i:s', $fileTimestamp);
                if ($fileTimestamp < $timestamp) {
                    $io->writeln('.');
                    $this->fileStorage->delete($filePath);
                    $this->logger->info(
                        sprintf('Fichier supprimé : %s modifié le %s', $filePath, $filePathDate)
                    );
                    ++$countDeleted;
                } else {
                    $io->write('.');
                }
            }
        }
        $io->success(sprintf('%d documents delete in tmp folder', $countDeleted));

        return Command::SUCCESS;
    }
}
