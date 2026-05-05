<?php

namespace App\Manager;

use App\Entity\Enum\DocumentType;
use App\Entity\File;
use App\Entity\Signalement;
use App\Entity\User;
use App\Factory\FileFactory;
use App\Repository\FileRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;

class FileManager extends Manager
{
    public function __construct(
        private readonly FileFactory $fileFactory,
        private readonly EntityManagerInterface $entityManager,
        private readonly FileRepository $fileRepository,
        ManagerRegistry $managerRegistry,
        string $entityName = File::class,
    ) {
        parent::__construct($managerRegistry, $entityName);
    }

    public function createOrUpdate(
        ?string $filename = null,
        ?string $title = null,
        ?Signalement $signalement = null,
        ?User $user = null,
        ?DocumentType $documentType = null,
        ?string $description = null,
    ): File {
        $file = $this->fileRepository->findOneBy(['filename' => $filename]);
        if (null === $file) {
            $file = $this->fileFactory->createInstanceFrom(
                filename: $filename,
                title: $title,
                signalement: $signalement,
                user: $user,
                documentType: $documentType,
                description: $description
            );
        }

        $file
            ->setTitle($title)
            ->setSignalement($signalement)
            ->setDocumentType($documentType ?? DocumentType::AUTRE)
            ->setDescription($description);

        $this->entityManager->persist($file);

        return $file;
    }
}
