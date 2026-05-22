<?php

namespace App\EventListener;

use App\Entity\Zone;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\Events;

/**
 * Handles automatic conversion between WKT text and MySQL GEOMETRY type for Zone entities.
 *
 * This listener allows developers to work with WKT (Well-Known Text) format in PHP code,
 * while the database stores data in optimized GEOMETRY format.
 *
 * Flow:
 * 1. onFlush: Intercept Zone insertions/updates, extract WKT, and prevent normal persist
 * 2. postFlush: Execute raw SQL to INSERT/UPDATE with ST_GeomFromText() conversion
 * 3. PostLoad (ZoneGeometryListener): Convert GEOMETRY back to WKT for PHP manipulation
 *
 * Priority 50 ensures this runs before EntityHistoryListener (default priority 0).
 */
#[AsDoctrineListener(event: Events::onFlush, priority: 50)]
#[AsDoctrineListener(event: Events::postFlush, priority: -50)]
class ZoneGeometryPersistListener
{
    /** @var array<int, array{zone: Zone, wkt: string, isNew: bool}> Zones pending GEOMETRY conversion */
    private array $pendingZones = [];

    public function onFlush(OnFlushEventArgs $args): void
    {
        $em = $args->getObjectManager();
        $uow = $em->getUnitOfWork();

        // Handle new Zone insertions
        foreach ($uow->getScheduledEntityInsertions() as $entity) {
            if (!$entity instanceof Zone) {
                continue;
            }

            $wkt = $entity->getArea();

            // Store zone info for postFlush processing
            // Since area is marked as insertable=false, Doctrine won't insert it
            // We'll update it in postFlush after getting the generated ID
            $this->pendingZones[] = [
                'zone' => $entity,
                'wkt' => $wkt,
            ];
        }

        // Handle Zone updates where area field changed
        foreach ($uow->getScheduledEntityUpdates() as $entity) {
            if (!$entity instanceof Zone) {
                continue;
            }

            // Since area is marked as updatable=false, we need to manually detect changes
            // by comparing current entity value with database value
            $currentWkt = $entity->getArea();

            // Skip if not WKT format (already binary GEOMETRY)
            if (!$this->isWktFormat($currentWkt)) {
                continue;
            }

            // Get the stored GEOMETRY from database
            $conn = $em->getConnection();
            $dbWkt = $conn->fetchOne(
                'SELECT ST_AsText(area) FROM zone WHERE id = :id',
                ['id' => $entity->getId()]
            );

            // Only update if WKT has changed
            if ($dbWkt !== $currentWkt) {
                $this->pendingZones[] = [
                    'zone' => $entity,
                    'wkt' => $currentWkt,
                ];
            }
        }
    }

    public function postFlush(PostFlushEventArgs $args): void
    {
        if (empty($this->pendingZones)) {
            return;
        }

        $em = $args->getObjectManager();
        $conn = $em->getConnection();

        foreach ($this->pendingZones as $data) {
            $zone = $data['zone'];
            $wkt = $data['wkt'];

            try {
                // Convert WKT to GEOMETRY using SQL function
                $conn->executeStatement(
                    'UPDATE zone SET area = ST_GeomFromText(:wkt) WHERE id = :id',
                    ['wkt' => $wkt, 'id' => $zone->getId()]
                );

                // Reload the GEOMETRY as WKT text for in-memory entity consistency
                $wktResult = $conn->fetchOne(
                    'SELECT ST_AsText(area) FROM zone WHERE id = :id',
                    ['id' => $zone->getId()]
                );

                if (false !== $wktResult) {
                    $zone->setArea($wktResult);
                }
            } catch (\Throwable $e) {
                // If conversion fails, log error
                error_log(sprintf(
                    'Failed to convert WKT to GEOMETRY for Zone #%d: %s',
                    $zone->getId(),
                    $e->getMessage()
                ));
            }
        }

        // Clear pending zones
        $this->pendingZones = [];
    }

    /**
     * Checks if a string value is in WKT format (starts with geometry type keyword).
     */
    private function isWktFormat(mixed $value): bool
    {
        if (!is_string($value)) {
            return false;
        }

        return (bool) preg_match('/^(POINT|POLYGON|LINESTRING|MULTIPOINT|MULTIPOLYGON|MULTILINESTRING|GEOMETRYCOLLECTION)/i', $value);
    }
}
