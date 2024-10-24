<?php

namespace App\Repository;

use App\Entity\Zone;
use App\Service\SearchZone;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Zone>
 */
class ZoneRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Zone::class);
    }

    public function findFilteredPaginated(SearchZone $searchZone, int $maxResult): Paginator
    {
        $qb = $this->createQueryBuilder('z');
        $qb->select('z', 'p', 't')
            ->leftJoin('z.partners', 'p')
            ->leftJoin('z.territory', 't')
            ->orderBy('z.name', 'ASC');

        if ($searchZone->getQueryName()) {
            $qb->andWhere('LOWER(z.name) LIKE :queryName');
            $qb->setParameter('queryName', '%'.strtolower($searchZone->getQueryName()).'%');
        }
        if ($searchZone->getTerritory()) {
            $qb->andWhere('z.territory = :territory')->setParameter('territory', $searchZone->getTerritory());
        }

        $firstResult = ($searchZone->getPage() - 1) * $maxResult;
        $qb->setFirstResult($firstResult)->setMaxResults($maxResult);

        return new Paginator($qb->getQuery());
    }

    public function findSignalementsByZone(Zone $zone): array
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = '
            SELECT s.uuid, s.reference, s.geoloc, s.adresse_occupant, s.cp_occupant, s.ville_occupant
            FROM signalement s
            JOIN zone z ON ST_Contains(ST_GeomFromText(z.area),
            Point(JSON_EXTRACT(s.geoloc, "$.lng"), JSON_EXTRACT(s.geoloc, "$.lat")))
            WHERE z.id = :zone AND s.territory_id = z.territory_id
            ORDER BY s.created_at DESC
            ';

        $resultSet = $conn->executeQuery($sql, ['zone' => $zone->getId()]);
        $list = $resultSet->fetchAllAssociative();
        foreach ($list as $key => $value) {
            $list[$key]['geoloc'] = json_decode($value['geoloc'], true);
        }

        return $list;
    }
}
