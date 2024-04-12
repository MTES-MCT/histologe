<?php

namespace App\Manager;

use App\Entity\Enum\DocumentType;
use App\Entity\File;
use App\Entity\Signalement;
use App\Entity\User;
use App\Factory\FileFactory;
use App\Repository\FileRepository;
use Doctrine\Persistence\ManagerRegistry;

class FileManager extends AbstractManager
{
    public function __construct(
        private readonly FileFactory $fileFactory,
        ManagerRegistry $managerRegistry,
        string $entityName = File::class
    ) {
        parent::__construct($managerRegistry, $entityName);
    }

    public function createOrUpdate(
        string $filename = null,
        string $title = null,
        string $type = null,
        ?Signalement $signalement = null,
        ?User $user = null,
        bool $flush = false,
        ?DocumentType $documentType = null,
        ?string $description = null
    ): File {
        /** @var FileRepository $fileRepository */
        $fileRepository = $this->getRepository();
        $file = $fileRepository->findOneBy(['filename' => $filename]);
        if (null === $file) {
            $file = $this->fileFactory->createInstanceFrom(
                filename: $filename,
                title: $title,
                type: $type,
                signalement: $signalement,
                user: $user,
                documentType: $documentType,
                description: $description
            );
        }

        $file
            ->setTitle($title)
            ->setFileType($type)
            ->setSignalement($signalement)
            ->setDocumentType($documentType ?? DocumentType::AUTRE)
            ->setDescription($description);

        $this->save($file, $flush);

        return $file;
    }

    public function updateSignalementFilesUser(
        Signalement $signalement,
        User $user,
    ) {
        foreach ($signalement->getFiles() as $file) {
            if (null === $file->getUploadedBy()) {
                $file->setUploadedBy($user);
                $this->save($file);
            }
        }
    }

    public function getFileFromSignalement(
        Signalement $signalement,
        int $fileId
    ): ?File {
        $fileCollection = $signalement->getFiles()->filter(
            function (File $file) use ($fileId) {
                return $fileId === $file->getId();
            }
        );
        if (!$fileCollection->isEmpty()) {
            return $fileCollection->current();
        }

        return null;
    }
}
