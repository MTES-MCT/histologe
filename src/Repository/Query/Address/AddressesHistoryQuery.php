<?php

namespace App\Repository\Query\Address;

use App\Entity\Address;
use App\Entity\Arrete;
use App\Entity\Enum\SignalementStatus;
use App\Entity\Signalement;
use App\Entity\User;
use App\Utils\Address\CommuneHelper;
use Doctrine\DBAL\Exception;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;

class AddressesHistoryQuery
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * @param array<mixed> $filters
     *
     * @return array<int, array<string, mixed>>
     */
    public function findAddressesWithHistory(
        User $user,
        array $filters = [],
    ): array {
        $statusList = [
            SignalementStatus::ACTIVE,
            SignalementStatus::NEED_VALIDATION,
            SignalementStatus::CLOSED,
        ];

        $qb = $this->entityManager->createQueryBuilder()
            ->from(Address::class, 'a')
            ->leftJoin(Signalement::class, 's', 'WITH',
                's.adresseOccupant = CONCAT(a.housenumber, \' \', a.street) AND s.cpOccupant = a.postCode AND s.villeOccupant = a.city AND s.statut IN (:statusList)'
            )
            ->leftJoin('a.arretes', 'ar')
            ->select(
                'a.id AS addressId',
                'CONCAT(a.housenumber, \' \', a.street) AS adresseOccupant',
                'a.postCode AS cpOccupant',
                'a.city AS villeOccupant',
                'a.point',
                'IDENTITY(a.territory) AS territoryId',
                's.id',
                's.uuid',
                's.createdAt',
                's.closedAt',
                's.reference',
                's.statut',
                's.geoloc',
                's.nomOccupant',
                's.prenomOccupant',
                's.nomProprio',
                'ar.id AS arreteId',
                'ar.dateArrete',
                'ar.typeArrete',
                'ar.mainLevee',
                'ar.dateMainLevee'
            )
            ->setParameter('statusList', $statusList)
            ->orderBy('a.street', 'ASC')
            ->addOrderBy('a.postCode', 'ASC')
            ->addOrderBy('a.city', 'ASC')
            ->addOrderBy('s.createdAt', 'ASC')
            ->addOrderBy('ar.dateArrete', 'ASC');

        // Ensure we have at least one signalement or one arrete
        $qb->andWhere('s.id IS NOT NULL OR ar.id IS NOT NULL');

        $queryDossiersMultiples = 'SELECT 1 FROM '.Signalement::class.' s2
                WHERE s2.adresseOccupant = CONCAT(a.housenumber, \' \', a.street)
                AND s2.cpOccupant = a.postCode
                AND s2.villeOccupant = a.city
                AND s2.statut IN (:statusList)
                AND s2.id != s.id';
        if (isset($filters['dossiersMultiples']) && 'oui' === $filters['dossiersMultiples']) {
            $qb->andWhere('EXISTS ('.$queryDossiersMultiples.')');
        } elseif (isset($filters['dossiersMultiples']) && 'non' === $filters['dossiersMultiples']) {
            $qb->andWhere('NOT EXISTS ('.$queryDossiersMultiples.')');
        }

        if ($user->isSuperAdmin()) {
            // pas de restrictions pour les SA
        } elseif ($user->isTerritoryAdmin()) {
            $qb->andWhere('a.territory IN (:territories)')->setParameter('territories', $user->getPartnersTerritories());
        } else {
            // inutilisé pour l'instant car la route est limité au RT, mais fonctionnel pour les autres profils.
            $qb->leftJoin('s.affectations', 'affectations')
                ->leftJoin('affectations.partner', 'partner')
                ->andWhere('partner IN (:partners)')
                ->setParameter('partners', $user->getPartners());
        }

        if (!empty($filters)) {
            $qb = $this->applyFilters($qb, $filters);
        }

        return $qb->getQuery()->getArrayResult();
    }

    /**
     * @param array<mixed> $filters
     *
     * @throws Exception
     */
    private function applyFilters(QueryBuilder $qb, array $filters): QueryBuilder
    {
        if (!empty($filters['adresse'])) {
            $qb->andWhere('LOWER(a.street) LIKE :adresse');
            $qb->setParameter('adresse', '%'.$filters['adresse'].'%');
        }

        if (!empty($filters['zone'])) {
            $connection = $this->entityManager->getConnection();
            $sql = '
                SELECT DISTINCT a2.id
                FROM address a2
                JOIN zone z ON z.id = :zoneId
                WHERE z.territory_id = a2.territory_id
                AND ST_Contains(
                    z.area,
                    a2.point
                ) = 1
            ';
            $stmt = $connection->prepare($sql);
            $stmt->bindValue('zoneId', $filters['zone']);
            $zonesAddresses = $stmt->executeQuery()->fetchAllAssociative();

            if (!empty($zonesAddresses)) {
                $addressIds = array_column($zonesAddresses, 'id');
                $qb->andWhere('a.id IN (:zonesAddresses)')
                   ->setParameter('zonesAddresses', $addressIds);
            } else {
                $qb->andWhere('a.id IS NULL');
            }
        }

        if (!empty($filters['cities'])) {
            foreach ($filters['cities'] as $city) {
                if (isset(CommuneHelper::COMMUNES_ARRONDISSEMENTS[$city])) {
                    $filters['cities'] = array_merge($filters['cities'], CommuneHelper::COMMUNES_ARRONDISSEMENTS[$city]);
                }
            }
            $qb->andWhere('a.city IN (:cities) OR a.postCode IN (:cities)')
                ->setParameter('cities', $filters['cities']);
        }
        if (!empty($filters['territories'])) {
            $qb->andWhere('a.territory IN (:territories)')
                ->setParameter('territories', $filters['territories']);
        }

        if (!empty($filters['housetypes'])) {
            if (\in_array('non_renseigne', $filters['housetypes'])) {
                $qb->andWhere('s.isLogementSocial IS NULL');
            } else {
                $qb->andWhere('s.isLogementSocial IN (:housetypes)')->setParameter('housetypes', $filters['housetypes']);
            }
        }

        if (!empty($filters['bailleurOuSyndic'])) {
            $qb
                ->andWhere('s.nomProprio LIKE :bailleurOuSyndic
                    OR s.denominationProprio LIKE :bailleurOuSyndic
                    OR s.nomSyndic LIKE :bailleurOuSyndic
                    OR s.denominationSyndic LIKE :bailleurOuSyndic')
                ->setParameter('bailleurOuSyndic', '%'.$filters['bailleurOuSyndic'].'%');
        }

        if (!empty($filters['typesArretes'])) {
            $qb->andWhere('ar.typeArrete IN (:typesArretes)')
                ->setParameter('typesArretes', $filters['typesArretes']);
        }

        return $qb;
    }
}
