<?php

namespace App\Factory;

use App\Entity\File;
use App\Entity\Signalement;

class FileFactory
{
    public function createInstanceFrom(
        string $filename = null,
        string $title = null,
        string $type = null,
        ?Signalement $signalement = null
    ): ?File {
        $file = (new File())
            ->setFilename($filename)
            ->setTitle($title)
            ->setFileType($type);

        if (null != $signalement) {
            $file->setSignalement($signalement);
        }

        return $file;
    }
}
