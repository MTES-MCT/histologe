<?php

namespace App\Command;

use App\Entity\File;
use App\Entity\Territory;
use App\Repository\FileRepository;
use App\Service\ImageManipulationHandler;
use Doctrine\ORM\EntityManagerInterface;
use League\Flysystem\FilesystemOperator;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:create-signalement-photo-variants',
    description: 'Create thumbnails and resized photos of signalement for a departement'
)]
class CreateSignalementPhotoVariantsCommand extends Command
{
    private SymfonyStyle $io;
    private int $i = 1;
    /** @var File[] */
    private array $files = [];

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly FilesystemOperator $fileStorage,
        private readonly ImageManipulationHandler $imageManipulationHandler,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('territory_zip', InputArgument::OPTIONAL, 'Territory zip to target');
    }

    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        $this->io = new SymfonyStyle($input, $output);
        $this->imageManipulationHandler->setUseTmpDir(false);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $territoryZip = $input->getArgument('territory_zip');
        $territory = null;
        if ($territoryZip) {
            $territory = $this->entityManager->getRepository(Territory::class)->findOneBy(['zip' => $territoryZip]);
            if (null === $territory) {
                $this->io->error('Territory does not exists');

                return Command::FAILURE;
            }
        }
        /** @var FileRepository $fileRepository */
        $fileRepository = $this->entityManager->getRepository(File::class);
        $this->files = $fileRepository->getPhotosWihoutVariants($territory);
        foreach ($this->files as $file) {
            $this->processFile($file);
            ++$this->i;
        }

        return Command::SUCCESS;
    }

    private function processFile(File $file): bool
    {
        try {
            if (!$this->fileStorage->fileExists($file->getFilename())) {
                $this->io->error('File '.$this->i.'/'.\count($this->files).' '.$file->getFilename().' ('.$file->getId().') : file does not exists');

                return false;
            }
            $variantNames = ImageManipulationHandler::getVariantNames($file->getFilename());
            $processed = false;
            if (!$this->fileStorage->fileExists($variantNames[ImageManipulationHandler::SUFFIX_RESIZE])) {
                $this->imageManipulationHandler->resize($file->getFilename());
                $fileSize = $this->fileStorage->fileSize($variantNames[ImageManipulationHandler::SUFFIX_RESIZE]);
                $file->setSize((string) $fileSize);
                $processed = true;
            }
            if (!$file->getSize()) {
                $fileSize = $this->fileStorage->fileSize($file->getFilename());
                $file->setSize((string) $fileSize);
            }
            if (!$this->fileStorage->fileExists($variantNames[ImageManipulationHandler::SUFFIX_THUMB])) {
                $this->imageManipulationHandler->thumbnail($file->getFilename());
                $processed = true;
            }
            $file->setIsVariantsGenerated(true);
            $this->entityManager->flush();
            if ($processed) {
                $this->io->success('File '.$this->i.'/'.\count($this->files).' '.$file->getFilename().' ('.$file->getId().') : variants created');
            } else {
                $this->io->success('File '.$this->i.'/'.\count($this->files).' '.$file->getFilename().' ('.$file->getId().') : variants already exists');
            }
        } catch (\Exception $exception) {
            $this->io->error('File '.$this->i.'/'.\count($this->files).' '.$file->getFilename().' ('.$file->getId().') : '.$exception->getMessage());

            return false;
        }

        return true;
    }
}
