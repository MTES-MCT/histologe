<?php

namespace App\Factory;

use App\Entity\File;
use App\Entity\Signalement;
use App\Entity\User;

class FileFactory
{
    public function createInstanceFrom(
        string $filename = null,
        string $title = null,
        string $type = null,
        ?Signalement $signalement = null,
        ?User $user = null,
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

        return $file;
    }
}
