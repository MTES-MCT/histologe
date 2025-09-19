<?php

namespace App\Command;

use App\Factory\FileFactory;
use App\Repository\FileRepository;
use App\Repository\TerritoryRepository;
use App\Repository\UserRepository;
use App\Service\UploadHandlerService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

#[AsCommand(
    name: 'app:duplicate-standalone-document-files',
    description: 'Duplicate standalone document files',
)]
class DuplicateStandaloneDocumentFilesCommand extends Command
{
    public function __construct(
        private readonly FileRepository $fileRepository,
        private readonly TerritoryRepository $territoryRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly FileFactory $fileFactory,
        private readonly UserRepository $userRepository,
        private readonly ParameterBagInterface $parameterBag,
        private readonly UploadHandlerService $uploadHandlerService,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        if (!$this->parameterBag->get('feature_new_document_space')) {
            $io->error('Cette commande n\'est pas executable car la fonctionnalité de nouvel espace document n\'est pas activée');

            return Command::SUCCESS;
        }

        $userAdmin = $this->userRepository->findOneBy(['email' => $this->parameterBag->get('admin_email')]);

        $allTerritories = $this->territoryRepository->findAll();

        $standaloneFiles = $this->fileRepository->findAllStandalone();
        foreach ($standaloneFiles as $file) {
            foreach ($allTerritories as $territory) {
                // Rename file with territory zip, just before extension, to avoid conflicts
                $extension = pathinfo($file->getFilename(), \PATHINFO_EXTENSION);
                $filenameWithoutExtension = pathinfo($file->getFilename(), \PATHINFO_FILENAME);
                $newFilename = $filenameWithoutExtension.'-'.$territory->getZip().'.'.$extension;
                $this->uploadHandlerService->copyToNewFilename($file->getFilename(), $newFilename);

                $newFile = $this->fileFactory->createInstanceFrom(
                    filename: $newFilename,
                    title: $file->getTitle().' - '.$territory->getName(),
                    description: $file->getDescription(),
                    user: $userAdmin,
                    isStandalone: true,
                    documentType: $file->getDocumentType(),
                    territory: $territory,
                );
                $newFile->setPartnerCompetence($file->getPartnerCompetence());
                $newFile->setPartnerType($file->getPartnerType());

                $this->entityManager->persist($newFile);

                $io->info('File '.$file->getFilename().' has been duplicated for territory zip '.$territory->getZip());
            }

            // Remove orginial file
            $this->uploadHandlerService->deleteFile($file);
        }

        $this->entityManager->flush();

        return Command::SUCCESS;
    }
}
