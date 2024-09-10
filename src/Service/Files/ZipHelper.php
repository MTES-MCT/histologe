<?php

namespace App\Service\Files;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\String\Slugger\SluggerInterface;

class ZipHelper
{
    public function __construct(
        private readonly ParameterBagInterface $parameterBag,
        private readonly SluggerInterface $slugger
    ) {
    }

    public function getZipFromBase64(string $base64): string
    {
        $zipData = base64_decode($base64);
        if (false === $zipData) {
            throw new \Exception('Échec du décodage base64.');
        }
        $zipPath = tempnam($this->parameterBag->get('uploads_tmp_dir'), 'zip');
        file_put_contents($zipPath, $zipData);

        return $zipPath;
    }

    public function extractZipFiles(string $zipPath): array
    {
        $zip = new \ZipArchive();
        if (true !== $zip->open($zipPath)) {
            throw new \Exception('Échec de l\'ouverture du fichier zip : '.$zipPath);
        }

        $extractedFiles = [];
        for ($i = 0; $i < $zip->numFiles; ++$i) {
            $filename = $zip->getNameIndex($i);
            $fileinfo = pathinfo($filename);
            if (!isset($fileinfo['extension'])) {
                continue;
            }
            $newFilePath = $this->parameterBag->get('uploads_tmp_dir').$this->slugger->slug($fileinfo['filename'].'-'.uniqid().'.'.$fileinfo['extension']);
            copy('zip://'.$zipPath.'#'.$filename, $newFilePath);
            $extractedFiles[$newFilePath] = $fileinfo['filename'].'.'.$fileinfo['extension'];
        }
        $zip->close();

        return $extractedFiles;
    }
}
