<?php

namespace App\Service\Signalement;

use App\Entity\Enum\DocumentType;
use App\Entity\File;
use App\Entity\Signalement;

class PhotoHelper
{
    /**
     * @return ?array<File>
     */
    public static function getPhotosBySlug(Signalement $signalement, string $desordrePrecisionSlug): ?array
    {
        $photos = $signalement->getPhotos()->filter(function (File $file) use ($desordrePrecisionSlug) {
            return DocumentType::PHOTO_SITUATION === $file->getDocumentType()
            && $file->getDesordreSlug() === $desordrePrecisionSlug;
        });

        return $photos->toArray();
    }

    /**
     * @return array<File>
     */
    public static function getSortedPhotos(Signalement $signalement, ?string $type = null): array
    {
        $photoList = $signalement->getPhotos()->toArray();
        $photoListByType = ['situation' => [], 'procédure' => [], 'visite' => []];

        foreach ($photoList as $photoItem) {
            if ($photoItem->isSituationImage()) {
                $photoListByType['situation'][] = $photoItem;
            } elseif ($photoItem->isProcedureImage()) {
                $photoListByType['procédure'][] = $photoItem;
            } else {
                $photoListByType['visite'][] = $photoItem;
            }
        }

        foreach ($photoListByType as $key => &$photoArray) {
            usort($photoArray, function (File $fileA, File $fileB) use ($key) {
                if ('situation' === $key) {
                    if (DocumentType::PHOTO_SITUATION === $fileA->getDocumentType() && DocumentType::PHOTO_SITUATION === $fileB->getDocumentType()) {
                        return $fileA->getId() <=> $fileB->getId();
                    }
                    if (DocumentType::PHOTO_SITUATION === $fileA->getDocumentType()) {
                        return -1;
                    }
                    if (DocumentType::PHOTO_SITUATION === $fileB->getDocumentType()) {
                        return 1;
                    }
                } elseif ('visite' === $key) {
                    $interventionA = $fileA->getIntervention();
                    $interventionB = $fileB->getIntervention();
                    if (null === $interventionA && null === $interventionB) {
                        return 0;
                    }
                    if (null === $interventionA) {
                        return 1;
                    }
                    if (null === $interventionB) {
                        return -1;
                    }

                    return $interventionA->getId() <=> $interventionB->getId();
                }

                return $fileA->getId() <=> $fileB->getId();
            });
        }
        if ($type) {
            return $photoListByType[$type] ?? [];
        }

        return array_merge($photoListByType['situation'], $photoListByType['procédure'], $photoListByType['visite']);
    }
}
