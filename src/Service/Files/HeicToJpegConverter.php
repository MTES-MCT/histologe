<?php

namespace App\Service\Files;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class HeicToJpegConverter
{
    public const FORMAT = ['.heic', '.heif'];
    public const HEIC_FORMAT = ['image/heic', 'image/heif'];

    public function __construct(
        private ParameterBagInterface $parameterBag
    ) {
    }

    /**
     * @throws \ImagickException
     */
    public function convert(string $filePath, ?string $newFilename = null): string
    {
        $pathInfo = pathinfo($filePath);
        $extension = $pathInfo['extension'] ?? '';
        if (empty($extension)) {
            $filenameInfo = pathinfo($newFilename);
            $extension = $filenameInfo['extension'];
        }

        if ('heic' === $extension) {
            // Read heic file
            $imageConvert = new \Imagick();
            $fileResourceRead = fopen($filePath, 'r');
            $imageConvert->readImageFile($fileResourceRead);
            $imageConvert->setImageFormat('jpeg');
            fclose($fileResourceRead);

            // Save to new jpg file
            $fileName = $newFilename
                ? str_replace('.heic', '.jpg', $newFilename)
                : $pathInfo['filename'].'-'.uniqid().'.jpg';

            $filePath = $this->parameterBag->get('uploads_tmp_dir').$fileName;
            $fileResourceWrite = fopen($filePath, 'w+');
            $imageConvert->writeImageFile($fileResourceWrite);
            fclose($fileResourceWrite);
        }

        return $filePath;
    }
}
