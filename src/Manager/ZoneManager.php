<?php

namespace App\Manager;

use App\Entity\Zone;
use App\Repository\ZoneRepository;
use Doctrine\ORM\EntityManagerInterface;

class ZoneManager
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ZoneRepository $zoneRepository,
    ) {
    }

    /**
     * Persists a new Zone with WKT area converted to GEOMETRY.
     * Uses raw SQL because Doctrine doesn't natively handle GEOMETRY type.
     */
    public function persistZone(Zone $zone): Zone
    {
        $wktArea = $zone->getArea();

        $conn = $this->entityManager->getConnection();

        // NB: We use direct mysql, so we don't save the history entry for the entity
        $conn->executeStatement(
            'INSERT INTO zone (territory_id, name, type, created_by_id, created_at, updated_at, area)
             VALUES (:territory_id, :name, :type, :created_by_id, NOW(), NOW(), ST_GeomFromText(:area))',
            [
                'territory_id' => $zone->getTerritory()->getId(),
                'name' => $zone->getName(),
                'type' => $zone->getType()->value,
                'created_by_id' => $zone->getCreatedBy()->getId(),
                'area' => $wktArea,
            ]
        );

        $zoneId = (int) $conn->lastInsertId();

        return $this->zoneRepository->find($zoneId);
    }

    /**
     * Updates a Zone entity, handling the area field with WKT to GEOMETRY conversion.
     */
    public function updateZone(Zone $zone): void
    {
        $wktArea = $zone->getArea();

        $this->flushWithAreaProtection($zone);

        // Update area with new WKT converted to GEOMETRY
        $this->updateZoneArea($zone, $wktArea);
    }

    /**
     * Flushes the entity manager while protecting the area GEOMETRY field.
     * Retrieves the raw GEOMETRY binary value and sets it back to prevent Doctrine
     * from attempting to persist WKT text as GEOMETRY.
     */
    public function flushWithAreaProtection(Zone $zone): void
    {
        // Get original GEOMETRY binary value to prevent Doctrine from trying to update it
        $originalArea = $this->zoneRepository->getRawAreaGeometry($zone);
        $zone->setArea($originalArea);

        $this->entityManager->flush();
    }

    /**
     * Updates a Zone's area field with WKT converted to GEOMETRY.
     */
    private function updateZoneArea(Zone $zone, string $wktArea): void
    {
        $conn = $this->entityManager->getConnection();

        // NB: We use direct mysql, so we don't save the history entry for the entity
        $conn->executeStatement(
            'UPDATE zone SET area = ST_GeomFromText(:wkt) WHERE id = :id',
            ['wkt' => $wktArea, 'id' => $zone->getId()]
        );
    }
}
