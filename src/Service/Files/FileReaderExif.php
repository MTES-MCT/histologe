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
        } catch (\Throwable $e) {
            $this->logger->error('Impossible de décoder l\'image pour le fichier. Erreur : '.$e->getMessage(), [
                'file_id' => $file->getId(),
            ]);
        }
        $datePriseDeVue = isset($image) ? trim($image->exif('EXIF.DateTimeOriginal')) : null;
        if (!$datePriseDeVue) {
            return;
        }

        try {
            $format = str_contains($datePriseDeVue, '.')
                ? '!Y:m:d H:i:s.u'
                : '!Y:m:d H:i:s';

            $date = \DateTimeImmutable::createFromFormat($format, $datePriseDeVue);
            $errors = \DateTimeImmutable::getLastErrors();
            if (
                false === $date
                || (false !== $errors && (
                    $errors['warning_count'] > 0
                    || $errors['error_count'] > 0
                ))
            ) {
                throw new \UnexpectedValueException('Format de date EXIF invalide : '.json_encode($errors));
            }
            $year = (int) $date->format('Y');
            $nextYear = (int) date('Y');
            ++$nextYear;
            // ticket #5801 : prevent error type "Invalid datetime format value: '-0001-11-30 00:00:00'"
            if ($year > 1970 && $year < $nextYear) {
                $file->setDatePriseDeVue($date);
            }
        } catch (\Throwable $e) {
            $this->logger->error('Impossible de convertir la donnée EXIF DateTimeOriginal en DateTimeImmutable pour le fichier.', [
                'file_id' => $file->getId(),
                'exif_date' => $datePriseDeVue,
                'exception' => $e,
            ]
            );
        }
    }
}
