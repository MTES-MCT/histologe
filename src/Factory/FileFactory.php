<?php

namespace App\Factory;

use App\Entity\Enum\DocumentType;
use App\Entity\File;
use App\Entity\Intervention;
use App\Entity\Signalement;
use App\Entity\User;
use App\Service\ImageManipulationHandler;
use App\Service\Signalement\SignalementDocumentTypeMapper;

class FileFactory
{
    public function createInstanceFrom(
        string $filename = null,
        string $title = null,
        string $type = null,
        ?Signalement $signalement = null,
        ?User $user = null,
        ?Intervention $intervention = null,
        ?DocumentType $documentType = null,
        ?string $desordreSlug = null,
        ?string $description = null,
        ?bool $isWaitingSuivi = false
    ): ?File {
        $file = (new File())
            ->setFilename($filename)
            ->setTitle($title)
            ->setFileType($type)
            ->setIsWaitingSuivi($isWaitingSuivi);
        if (null !== $signalement) {
            $file->setSignalement($signalement);
        }

        if (null !== $user) {
            $file->setUploadedBy($user);
        }

        if (null !== $intervention) {
            $file->setIntervention($intervention);
        }

        if (null !== $documentType) {
            $file->setDocumentType($documentType);
        } else {
            $file->setDocumentType(DocumentType::AUTRE);
        }

        if (null !== $desordreSlug) {
            $file->setDesordreSlug($desordreSlug);
        }

        if (null !== $description) {
            $file->setDescription($description);
        }

        return $file;
    }

    /**
     * @param array $file The array representing the file.
     *                    - 'slug' (string): The slug value.
     *                    - 'file' (string): The file path.
     *                    - 'titre' (string): The title of the file.
     *                    - 'description' (string): The description of the file.
     */
    public function createFromFileArray(
        array $file,
        ?Signalement $signalement = null,
        ?User $user = null,
        ?Intervention $intervention = null,
    ): ?File {
        $documentType = SignalementDocumentTypeMapper::map($file['slug']);
        $desordreSlug = DocumentType::PHOTO_SITUATION === $documentType ? $file['slug'] : null;
        $fileDescription = $file['description'] ?? null;

        return $this->createInstanceFrom(
            filename: $file['file'],
            title: $file['titre'],
            type: $this->getFileType($file, $documentType),
            signalement: $signalement,
            user: $user,
            intervention: $intervention,
            documentType: $documentType,
            desordreSlug: $desordreSlug,
            description: $fileDescription,
        );
    }

    private function getFileType(array $file, DocumentType $documentType)
    {
        if ('photos' === $file['key']
            && File::FILE_TYPE_PHOTO === $documentType->mapFileType()
            && \in_array(
                strtolower(pathinfo($file['file'], \PATHINFO_EXTENSION)),
                ImageManipulationHandler::IMAGE_EXTENSION
            )
        ) {
            return File::FILE_TYPE_PHOTO;
        }

        return File::FILE_TYPE_DOCUMENT;
    }
}
