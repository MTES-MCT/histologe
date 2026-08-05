<?php

namespace App\Repository;

use App\Entity\Enum\SignalementStatus;
use App\Entity\Signalement;
use App\Entity\Territory;
use App\Entity\User;
use App\Entity\Zone;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\ArrayParameterType;
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

    /**
     * @return array<int, array<string, mixed>>
     */
    public function findSignalementsByZone(Zone $zone): array
    {
        $conn = $this->getEntityManager()->getConnection();
        $parameters = [];
        $types = [];
        $parameters['zone'] = $zone->getId();
        $parameters['statut_list'] = SignalementStatus::excludedStatusesValue();
        $types['statut_list'] = ArrayParameterType::STRING;
        $sql = '
            SELECT s.uuid, s.reference, ST_Y(address.point) AS lat, ST_X(address.point) AS lng,
                address.housenumber, address.street, address.post_code, address.city
            FROM signalement s
            JOIN address ON s.address_id = address.id
            JOIN zone z ON ST_Contains(z.area, address.point)
            WHERE z.id = :zone AND address.territory_id = z.territory_id
            AND s.statut NOT IN (:statut_list)
            ORDER BY s.created_at DESC
            ';

        $resultSet = $conn->executeQuery($sql, $parameters, $types);

        return $resultSet->fetchAllAssociative();
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
            JOIN address ON s.address_id = address.id
            JOIN zone z ON ST_Contains(z.area, address.point)
            WHERE s.id = :signalement AND address.territory_id = z.territory_id
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
        $qb = $this->createQueryBuilder('z')
            ->select('partial z.{id, name, type}'); // Exclusion des colonnes inutiles (notamment area qui pèse sur le game)

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
