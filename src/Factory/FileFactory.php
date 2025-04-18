<?php

namespace App\Factory;

use App\Entity\Enum\DocumentType;
use App\Entity\File;
use App\Entity\Intervention;
use App\Entity\Signalement;
use App\Entity\User;
use App\Service\Signalement\SignalementDocumentTypeMapper;

class FileFactory
{
    public function createInstanceFrom(
        ?string $filename = null,
        ?string $title = null,
        ?Signalement $signalement = null,
        ?User $user = null,
        ?Intervention $intervention = null,
        ?DocumentType $documentType = null,
        ?string $desordreSlug = null,
        ?string $description = null,
        ?bool $isWaitingSuivi = false,
        ?bool $isTemp = false,
        ?\DateTimeImmutable $scannedAt = null,
        ?bool $isVariantsGenerated = false,
        ?bool $isSuspicious = false,
    ): ?File {
        $file = (new File())
            ->setFilename($filename)
            ->setTitle($title)
            ->setFileType($this->getFileType($filename, $documentType ?? DocumentType::AUTRE))
            ->setIsWaitingSuivi($isWaitingSuivi)
            ->setIsTemp($isTemp);
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

        if (null !== $scannedAt) {
            $file->setScannedAt($scannedAt);
        }

        if (null !== $isVariantsGenerated) {
            $file->setIsVariantsGenerated($isVariantsGenerated);
        }

        if (null !== $isSuspicious) {
            $file->setIsSuspicious($isSuspicious);
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
            signalement: $signalement,
            user: $user,
            intervention: $intervention,
            documentType: $documentType,
            desordreSlug: $desordreSlug,
            description: $fileDescription,
        );
    }

    private function getFileType(string $filename, DocumentType $documentType)
    {
        $ext = strtolower(pathinfo($filename, \PATHINFO_EXTENSION));
        if ((File::FILE_TYPE_PHOTO === $documentType->mapFileType() || DocumentType::AUTRE === $documentType)
            && \in_array($ext, File::IMAGE_EXTENSION)
            && 'pdf' !== $ext
        ) {
            return File::FILE_TYPE_PHOTO;
        }

        return File::FILE_TYPE_DOCUMENT;
    }
}
