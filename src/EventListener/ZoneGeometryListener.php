<?php

namespace App\EventListener;

use App\Entity\Zone;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\PostLoadEventArgs;
use Doctrine\ORM\Events;

#[AsDoctrineListener(event: Events::postLoad)]
class ZoneGeometryListener
{
    public function postLoad(PostLoadEventArgs $args): void
    {
        $entity = $args->getObject();

        if (!$entity instanceof Zone) {
            return;
        }

        $em = $args->getObjectManager();

        // If area field is not loaded (partial select excluded it), skip conversion
        try {
            $areaValue = $entity->getArea();
        } catch (\Throwable) {
            // Field not loaded, skip
            return;
        }

        // Check if empty
        if (empty($areaValue)) {
            return;
        }

        // Only convert if area is not already WKT text (to avoid re-converting)
        // WKT typically starts with geometry types like POLYGON, POINT, etc.
        // Binary GEOMETRY data will not be valid UTF-8 and will not match WKT pattern
        if (is_string($areaValue) && preg_match('/^(POINT|POLYGON|LINESTRING|MULTIPOINT|MULTIPOLYGON|MULTILINESTRING|GEOMETRYCOLLECTION)/i', $areaValue)) {
            // Already WKT, skip
            return;
        }

        // Convert GEOMETRY binary data to WKT text for display
        $conn = $em->getConnection();

        try {
            $wktArea = $conn->executeQuery(
                'SELECT ST_AsText(area) as area_wkt FROM zone WHERE id = :id',
                ['id' => $entity->getId()]
            )->fetchOne();

            if ($wktArea) {
                $entity->setArea($wktArea);
            }
        } catch (\Throwable) {
            // If conversion fails, leave the binary value as-is
            // This prevents errors but the value won't be usable in templates
        }
    }
}
