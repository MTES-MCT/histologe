<?php

namespace App\Service\Files;

use App\Entity\File;
use Intervention\Image\ImageManager;
use Psr\Log\LoggerInterface;

class FileReaderExif
{
    public function __construct(
        private readonly ImageManager $imageManager,
        private readonly ImageVariantProvider $imageVariantProvider,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function setDatePriseDeVueFromExifData(File $file): void
    {
        try {
            $image = $this->imageManager->decode($this->imageVariantProvider->getFileVariant($file->getFilename()));
        } catch (\Exception $e) {
            $this->logger->error('Impossible de décoder l\'image pour le fichier : '.$file->getFilename().'. Erreur : '.$e->getMessage());
        }
        $datePriseDeVue = isset($image) ? $image->exif('EXIF.DateTimeOriginal') : null;
        if ($datePriseDeVue) {
            try {
                $date = new \DateTimeImmutable($datePriseDeVue);
                $year = (int) $date->format('Y');
                $nextYear = (int) date('Y');
                ++$nextYear;
                // ticket #5801 : prevent error type "Invalid datetime format value: '-0001-11-30 00:00:00'"
                if ($year > 1970 && $year < $nextYear) {
                    $file->setDatePriseDeVue($date);
                }
            } catch (\Exception $e) {
                $this->logger->error('Impossible de convertir la donnée EXIF DateTimeOriginal ("'.$datePriseDeVue.'") en DateTimeImmutable pour le fichier : '.$file->getFilename());
            }
        }
    }
}
