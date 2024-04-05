<?php

namespace App\Service\Signalement;

use App\Entity\Enum\DocumentType;
use App\Entity\File;
use App\Entity\Signalement;

class PhotoHelper
{
    public static function getPhotosBySlug(Signalement $signalement, string $desordrePrecisionSlug): ?array
    {
        $photos = $signalement->getPhotos()->filter(function (File $file) use ($desordrePrecisionSlug) {
            return DocumentType::PHOTO_SITUATION === $file->getDocumentType()
            && $file->getDesordreSlug() === $desordrePrecisionSlug;
        });

        return $photos->toArray();
    }

    public static function getSortedPhotos(Signalement $signalement): ?array
    {
        $photoList = $signalement->getPhotos()->toArray();
        $photoListByType = [
            DocumentType::PHOTO_SITUATION->value => [],
            DocumentType::PHOTO_VISITE->value => [],
            DocumentType::AUTRE->value => [],
        ];

        foreach ($photoList as $photoItem) {
            $type = $photoItem->getDocumentType();
            $photoListByType[$type->value][] = $photoItem;
        }

        foreach ($photoListByType as &$photoArray) {
            usort($photoArray, function (File $fileA, File $fileB) {
                if (DocumentType::PHOTO_SITUATION === $fileA->getDocumentType()) {
                    return $fileA->getId() <=> $fileB->getId();
                }
                if (DocumentType::PHOTO_VISITE === $fileA->getDocumentType()) {
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

        return array_merge(
            $photoListByType[DocumentType::PHOTO_SITUATION->value],
            $photoListByType[DocumentType::AUTRE->value],
            $photoListByType[DocumentType::PHOTO_VISITE->value]
        );
    }
}
