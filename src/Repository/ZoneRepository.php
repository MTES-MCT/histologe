<?php

namespace App\Repository;

use App\Entity\Enum\SignalementStatus;
use App\Entity\Signalement;
use App\Entity\Territory;
use App\Entity\User;
use App\Entity\Zone;
use App\Service\ListFilters\SearchZone;
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
        $qb->select('z', 'p', 'ep', 't')
            ->leftJoin('z.partners', 'p')
            ->leftJoin('z.excludedPartners', 'ep')
            ->leftJoin('z.territory', 't');

        if (!empty($searchZone->getOrderType())) {
            [$orderField, $orderDirection] = explode('-', $searchZone->getOrderType());
            $qb->orderBy($orderField, $orderDirection);
        } else {
            $qb->orderBy('z.name', 'ASC');
        }

        if ($searchZone->getQueryName()) {
            $qb->andWhere('LOWER(z.name) LIKE :queryName');
            $qb->setParameter('queryName', '%'.strtolower($searchZone->getQueryName()).'%');
        }
        if ($searchZone->getTerritory()) {
            $qb->andWhere('z.territory = :territory')->setParameter('territory', $searchZone->getTerritory());
        } elseif (!$searchZone->getUser()->isSuperAdmin()) {
            $qb->andWhere('z.territory IN (:territories)')->setParameter('territories', $searchZone->getUser()->getPartnersTerritories());
        }
        if ($searchZone->getType()) {
            $qb->andWhere('z.type = :type')->setParameter('type', $searchZone->getType()->value);
        }

        $firstResult = ($searchZone->getPage() - 1) * $maxResult;
        $qb->setFirstResult($firstResult)->setMaxResults($maxResult);

        return new Paginator($qb->getQuery());
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function findSignalementsByZone(Zone $zone): array
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = '
            SELECT s.uuid, s.reference, s.geoloc, s.adresse_occupant, s.cp_occupant, s.ville_occupant
            FROM signalement s
            JOIN zone z ON ST_Contains(ST_GeomFromText(z.area),
            Point(JSON_EXTRACT(s.geoloc, "$.lng"), JSON_EXTRACT(s.geoloc, "$.lat")))
            WHERE z.id = :zone AND s.territory_id = z.territory_id
            AND s.statut NOT IN (:status_draft, :status_archived)
            ORDER BY s.created_at DESC
            ';

        $resultSet = $conn->executeQuery($sql, ['zone' => $zone->getId(), 'status_draft' => SignalementStatus::DRAFT->value, 'status_archived' => SignalementStatus::ARCHIVED->value, 'status_draft_archived' => SignalementStatus::DRAFT_ARCHIVED->value]);
        $list = $resultSet->fetchAllAssociative();
        foreach ($list as $key => $value) {
            $list[$key]['geoloc'] = json_decode($value['geoloc'], true);
        }

        return $list;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function findZonesBySignalement(Signalement $signalement): array
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = '
            SELECT z.name
            FROM signalement s
            JOIN zone z ON ST_Contains(ST_GeomFromText(z.area),
            Point(JSON_EXTRACT(s.geoloc, "$.lng"), JSON_EXTRACT(s.geoloc, "$.lat")))
            WHERE s.id = :signalement AND z.territory_id = z.territory_id
            ORDER BY z.name ASC
            ';
        $resultSet = $conn->executeQuery($sql, ['signalement' => $signalement->getId()]);

        return $resultSet->fetchAllAssociative();
    }

    /**
     * @return array<int, Zone>
     */
    public function findForUserAndTerritory(User $user, ?Territory $territory): array
    {
        $qb = $this->createQueryBuilder('z');
        if (!$user->isSuperAdmin()) {
            $qb->andWhere('z.territory IN (:territories)')->setParameter('territories', $user->getPartnersTerritories());
        }
        if ($territory) {
            $qb->andWhere('z.territory = :territory')->setParameter('territory', $territory);
        }
        $qb->orderBy('z.name', 'ASC');

        return $qb->getQuery()->getResult();
    }
}
