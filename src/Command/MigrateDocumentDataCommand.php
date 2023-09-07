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
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:migrate-document-data',
    description: 'Migrate document data from signalement table to file table',
)]
class MigrateDocumentDataCommand extends Command
{
    private const FLUSH_COUNT = 1000;

    public function __construct(
        readonly private EntityManagerInterface $entityManager,
        readonly private SignalementRepository $signalementRepository,
        readonly private UserRepository $userRepository,
        readonly private FileRepository $fileRepository,
    ) {
        parent::__construct();
    }

    public function configure(): void
    {
        $this->addArgument(
            'imported',
            InputArgument::OPTIONAL,
            'Signalement imported',
            true
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $isImported = $input->getArgument('imported');
        $countSignalement = $this->signalementRepository->count(['isImported' => $isImported]);
        $signalements = $this->signalementRepository->findBy(['isImported' => $isImported]);

        /** @var QuestionHelper $helper */
        $helper = $this->getHelper('question');
        $question = new ConfirmationQuestion(
            sprintf(
                'Voulez vous charger %s signalements %s (y pour oui, n pour non) ? ',
                $countSignalement,
                $isImported ? 'importés' : 'non importés'
            ),
            false
        );

        if (!$helper->ask($input, $output, $question)) {
            return Command::SUCCESS;
        }

        $progressBar = new ProgressBar($output, $countSignalement);
        $progressBar->start();
        foreach ($signalements as $index => $signalement) {
            $photos = $signalement->getPhotos();
            $this->loadFileByType($signalement, $photos, File::FILE_TYPE_PHOTO);

            $documents = $signalement->getDocuments();
            $this->loadFileByType($signalement, $documents, File::FILE_TYPE_DOCUMENT);
            $progressBar->advance();

            $this->loadFileIntervention($signalement);

            if (0 === $index % self::FLUSH_COUNT) {
                $this->entityManager->flush();
            }
        }

        $this->entityManager->flush();
        $io->success(sprintf(
            'Les données de signalement %s ont bien été déplacées vers la table File.',
            $isImported ? 'importés' : 'non importés'
        ));
        $progressBar->finish();

        return Command::SUCCESS;
    }

    private function loadFileByType(Signalement $signalement, array $fileList, string $type): void
    {
        foreach ($fileList as $fileItem) {
            if (isset($fileItem['file'])) {
                $file = $this->fileRepository->findOneBy(['filename' => $fileItem['file']]);
                if (null === $file) {
                    $file = new File();
                }

                if (!isset($fileItem['date'])
                    || false === $createdAt = \DateTimeImmutable::createFromFormat('d.m.Y', $fileItem['date'])
                ) {
                    $createdAt = $signalement->getCreatedAt();
                }

                $file
                    ->setFileType($type)
                    ->setFilename($fileItem['file'])
                    ->setTitle($fileItem['titre'] ?? $fileItem['file'])
                    ->setUploadedBy(isset($fileItem['user'])
                        ? $this->userRepository->find($fileItem['user'])
                        : null)
                    ->setCreatedAt($createdAt)
                    ->setSignalement($signalement);

                $this->entityManager->persist($file);
                unset($file);
            }
        }
    }

    private function loadFileIntervention(Signalement $signalement): void
    {
        foreach ($signalement->getInterventions() as $intervention) {
            foreach ($intervention->getDocuments() as $document) {
                $file = $this->fileRepository->findOneBy(['filename' => $document]);
                if (null === $file) {
                    $file = new File();
                }

                $file
                    ->setIntervention($intervention)
                    ->setFilename($document)
                    ->setTitle($document)
                    ->setCreatedAt($intervention->getCreatedAt())
                    ->setSignalement($signalement)
                    ->setFileType('pdf' === pathinfo($document, \PATHINFO_EXTENSION)
                        ? File::FILE_TYPE_DOCUMENT
                        : File::FILE_TYPE_PHOTO
                    );

                $this->entityManager->persist($file);
                unset($file);
            }
        }
    }
}
