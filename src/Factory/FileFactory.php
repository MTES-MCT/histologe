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
        string $filename = null,
        string $title = null,
        string $type = null,
        ?Signalement $signalement = null,
        ?User $user = null,
        ?Intervention $intervention = null,
        ?DocumentType $documentType = null,
        ?string $desordreSlug = null,
    ): ?File {
        $file = (new File())
            ->setFilename($filename)
            ->setTitle($title)
            ->setFileType($type);
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
        }

        if (null !== $desordreSlug) {
            $file->setDesordreSlug($desordreSlug);
        }

        return $file;
    }

    /**
     * @param array $file The array representing the file.
     *                    - 'slug' (string): The slug value.
     *                    - 'file' (string): The file path.
     *                    - 'titre' (string): The title of the file.
     */
    public function createFromFileArray(
        array $file,
        ?Signalement $signalement = null,
        ?User $user = null,
        ?Intervention $intervention = null,
    ): ?File {
        $documentType = SignalementDocumentTypeMapper::map($file['slug']);
        $desordreSlug = DocumentType::SITUATION === $documentType ? $file['slug'] : null;

        return $this->createInstanceFrom(
            filename: $file['file'],
            title: $file['titre'],
            type: 'pdf' === pathinfo($file['file'], \PATHINFO_EXTENSION)
                ? File::FILE_TYPE_DOCUMENT
                : File::FILE_TYPE_PHOTO,
            signalement: $signalement,
            user: $user,
            intervention: $intervention,
            documentType: SignalementDocumentTypeMapper::map($file['slug']),
            desordreSlug: $desordreSlug
        );
    }
}
