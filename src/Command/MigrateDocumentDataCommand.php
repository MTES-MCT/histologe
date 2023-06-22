<?php

namespace App\Command;

use App\Entity\File;
use App\Entity\Signalement;
use App\Repository\FileRepository;
use App\Repository\SignalementRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:migrate-document-data',
    description: 'Migrate document data from signalement table to file table',
)]
class MigrateDocumentDataCommand extends Command
{
    public function __construct(
        readonly private EntityManagerInterface $entityManager,
        readonly private SignalementRepository $signalementRepository,
        readonly private UserRepository $userRepository,
        readonly private FileRepository $fileRepository,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $signalements = $this->signalementRepository->findAll();
        $progressBar = new ProgressBar($output, \count($signalements));
        $progressBar->start();
        foreach ($signalements as $signalement) {
            $photos = $signalement->getPhotos();
            $this->loadFileByType($signalement, $photos, File::FILE_TYPE_PHOTO);

            $documents = $signalement->getDocuments();
            $this->loadFileByType($signalement, $documents, File::FILE_TYPE_DOCUMENT);
            $progressBar->advance();
        }

        $this->entityManager->flush();
        $io->success('Documents information have been moved from signalement table to file table.');
        $progressBar->finish();

        return Command::SUCCESS;
    }

    private function loadFileByType(Signalement $signalement, array $fileList, string $type): void
    {
        foreach ($fileList as $fileItem) {
            $file = $this->fileRepository->findOneBy(['filename' => $fileItem['file']]);
            if (null === $file) {
                $file = new File();
            }

            $file
                ->setFileType($type)
                ->setFilename($fileItem['file'])
                ->setTitle($fileItem['titre'])
                ->setUser(isset($fileItem['user']) ? $this->userRepository->find($fileItem['user']) : null)
                ->setCreatedAt(
                    isset($fileItem['date'])
                        ? \DateTimeImmutable::createFromFormat('d.m.Y', $fileItem['date'])
                        : $signalement->getCreatedAt()
                )
                ->setSignalement($signalement);

            $this->entityManager->persist($file);
        }
    }
}
