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
     * @return ?array<File>
     */
    public static function getSortedPhotos(Signalement $signalement): ?array
    {
        $photoList = $signalement->getPhotos()->toArray();
        $photoListByType = ['situation' => [], 'procedure' => [], 'visite' => []];

        foreach ($photoList as $photoItem) {
            if ($photoItem->isSituationImage()) {
                $photoListByType['situation'][] = $photoItem;
            } elseif ($photoItem->isProcedureImage()) {
                $photoListByType['procedure'][] = $photoItem;
            } else {
                $photoListByType['visite'][] = $photoItem;
            }
        }

        foreach ($photoListByType as $key => &$photoArray) {
            usort($photoArray, function (File $fileA, File $fileB) use ($key) {
                if ('visite' === $key) {
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

        return array_merge($photoListByType['situation'], $photoListByType['procedure'], $photoListByType['visite']);
    }
}
