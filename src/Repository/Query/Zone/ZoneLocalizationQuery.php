<?php

namespace App\Repository\Query\Zone;

use App\Entity\Territory;
use Doctrine\DBAL\Exception;
use Doctrine\ORM\EntityManagerInterface;

class ZoneLocalizationQuery
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * @return array<int>
     *
     * @throws Exception
     */
    public function findZoneIdsContainingPoint(Territory $territory, float $lng, float $lat): array
    {
        $sql = '
            SELECT z.id
            FROM zone z
            WHERE z.territory_id = :territory
            AND ST_Contains(z.area, Point(:lng, :lat))
            ';

        $resultSet = $this->entityManager->getConnection()->executeQuery($sql, [
            'territory' => $territory->getId(),
            'lng' => $lng,
            'lat' => $lat,
        ]);

        return array_map('intval', $resultSet->fetchFirstColumn());
    }
}
