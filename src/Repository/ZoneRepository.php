<?php

namespace App\Repository;

use App\Entity\Zone;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Zone>
 *
 * @method Zone|null find($id, $lockMode = null, $lockVersion = null)
 * @method Zone|null findOneBy(array $criteria, array $orderBy = null)
 * @method Zone[]    findAll()
 * @method Zone[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ZoneRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Zone::class);
    }

    public function findLocationsByZone(Zone $zone): array
    {
        $conn = $this->getEntityManager()->getConnection();

        $sql = '
            SELECT location.*
            FROM location
            JOIN zone ON ST_Contains(ST_GeomFromText(zone.wkt), Point(location.lng, location.lat)) WHERE zone.id = :zone
            ';

        $resultSet = $conn->executeQuery($sql, ['zone' => $zone->getId()]);

        return $resultSet->fetchAllAssociative();
    }
}
