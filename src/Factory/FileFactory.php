<?php

namespace App\Factory;

use App\Entity\Enum\DocumentType;
use App\Entity\File;
use App\Entity\Intervention;
use App\Entity\Signalement;
use App\Entity\Territory;
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
        ?bool $isStandalone = false,
        ?Territory $territory = null,
    ): ?File {
        $extension = strtolower(pathinfo($filename, \PATHINFO_EXTENSION));
        $file = (new File())
            ->setFilename($filename)
            ->setTitle($title)
            ->setExtension($extension)
            ->setIsWaitingSuivi($isWaitingSuivi)
            ->setIsTemp($isTemp);

        if (null !== $documentType) {
            $file->setDocumentType($documentType);
        } else {
            $file->setDocumentType(DocumentType::AUTRE);
        }

        if (null !== $signalement) {
            $file->setSignalement($signalement);
        }

        if (null !== $user) {
            $file->setUploadedBy($user);
        }

        if (null !== $intervention) {
            $file->setIntervention($intervention);
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

        if (null !== $isStandalone) {
            $file->setIsStandalone($isStandalone);
        }

        if (null !== $territory) {
            $file->setTerritory($territory);
        }

        return $file;
    }

    /**
     * @param array<string, mixed> $file The array representing the file.
     *                                   - 'slug' (string): The slug value.
     *                                   - 'file' (string): The file path.
     *                                   - 'titre' (string): The title of the file.
     *                                   - 'description' (string): The description of the file.
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
}
