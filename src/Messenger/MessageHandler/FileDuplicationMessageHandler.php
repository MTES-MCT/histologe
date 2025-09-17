<?php

namespace App\Messenger\MessageHandler;

use App\Entity\Enum\DocumentType;
use App\Entity\File;
use App\Messenger\Message\FileDuplicationMessage;
use App\Repository\FileRepository;
use App\Repository\TerritoryRepository;
use App\Service\UploadHandlerService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Uid\Uuid;

#[AsMessageHandler]
class FileDuplicationMessageHandler
{
    public function __construct(
        private readonly FileRepository $fileRepository,
        private readonly TerritoryRepository $territoryRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly UploadHandlerService $uploadHandlerService,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function __invoke(FileDuplicationMessage $message): void
    {
        $this->logger->info('Start handling FileDuplicationMessage', [
            'fileId' => $message->getFileId(),
        ]);

        $originalFile = $this->fileRepository->find($message->getFileId());
        if (!$originalFile) {
            $this->logger->error('File not found for duplication', [
                'fileId' => $message->getFileId(),
            ]);

            return;
        }

        $allTerritories = $this->territoryRepository->findAll();
        $variantsGenerated = $originalFile->isIsVariantsGenerated();

        foreach ($allTerritories as $territory) {
            // Avoids duplicate visit grids for a same territory
            if (DocumentType::GRILLE_DE_VISITE === $originalFile->getDocumentType()) {
                $existingVisitGrid = $this->fileRepository->findOneBy([
                    'territory' => $territory,
                    'documentType' => DocumentType::GRILLE_DE_VISITE,
                ]);
                if ($existingVisitGrid) {
                    continue;
                }
            }

            $newFile = clone $originalFile;
            $newFile->setTerritory($territory);

            // Reset UUID and detach from entity manager to avoid constraint violations
            $newFile->setUuid(Uuid::v4());
            $this->entityManager->detach($newFile);

            $this->duplicateFileForTerritory($newFile, $variantsGenerated);
        }

        // Remove original file once all copies are made
        $this->entityManager->remove($originalFile);

        $this->entityManager->flush();

        $this->logger->info('FileDuplicationMessage handled successfully', [
            'fileId' => $message->getFileId(),
            'territoriesCount' => count($allTerritories),
        ]);
    }

    private function duplicateFileForTerritory(File $file, bool $variantsGenerated): void
    {
        $file->setTitle($file->getTitle().' - '.$file->getTerritory()->getZip());
        $extension = pathinfo($file->getFilename(), \PATHINFO_EXTENSION);
        $filenameWithoutExtension = pathinfo($file->getFilename(), \PATHINFO_FILENAME);
        $newFilename = $filenameWithoutExtension.'-'.$file->getTerritory()->getZip().'.'.$extension;

        $this->uploadHandlerService->copyToNewFilename($file->getFilename(), $newFilename);
        $file->setFilename($newFilename);

        if ($variantsGenerated) {
            $this->uploadHandlerService->copyPhotoVariantsToNewFilename($file->getFilename(), $newFilename);
        }

        $file->setScannedAt(new \DateTimeImmutable());
        $file->setIsStandalone(true);

        $this->entityManager->persist($file);
    }
}
