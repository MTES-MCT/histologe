<?php

namespace App\Service\Files;

class TmpFileWriter
{
    public function putContents(string $filepath, string $content): void
    {
        $dir = \dirname($filepath);
        if (!is_dir($dir) && !mkdir($dir, 0775, true)) {
            throw new \Exception(sprintf('Impossible de créer le dossier : %s', $dir));
        }
        file_put_contents($filepath, $content);
    }
}
