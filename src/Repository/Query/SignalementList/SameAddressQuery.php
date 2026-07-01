<?php

namespace App\Repository\Query\SignalementList;

use App\Entity\Enum\SignalementStatus;
use App\Entity\Signalement;
use App\Entity\User;
use App\Utils\Address\CommuneHelper;
use Doctrine\DBAL\Exception;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;

class SameAddressQuery
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
    public function findSameAddressFiltered(
        User $user,
        array $filters = [],
    ): array {
        $statusList = [
            SignalementStatus::ACTIVE,
            SignalementStatus::NEED_VALIDATION,
            SignalementStatus::CLOSED,
        ];

        // @todo reprendre cette requête pour repartir de l'entité Adresse
        $qb = $this->entityManager->createQueryBuilder()
            ->from(Signalement::class, 's')
            ->select(
                's.id',
                's.uuid',
                's.createdAt',
                's.closedAt',
                's.reference',
                's.statut',
                's.adresseOccupant',
                's.cpOccupant',
                's.villeOccupant',
                's.geoloc',
                's.nomOccupant',
                's.prenomOccupant',
                's.nomProprio',
                'IDENTITY(s.territory) AS territoryId',
            )
            ->where('s.statut IN (:statusList)')
            ->setParameter('statusList', $statusList)
            ->orderBy('s.adresseOccupant', 'ASC')
            ->addOrderBy('s.cpOccupant', 'ASC')
            ->addOrderBy('s.villeOccupant', 'ASC')
            ->addOrderBy('s.createdAt', 'ASC');

        $queryDossiersMultiples = 'SELECT 1 FROM '.Signalement::class.' s2
                WHERE s2.adresseOccupant = s.adresseOccupant
                AND s2.cpOccupant = s.cpOccupant
                AND s2.villeOccupant = s.villeOccupant
                AND s2.statut IN (:statusList)
                AND s2.id != s.id';
        if (empty($filters) || (isset($filters['dossiersMultiples']) && 'oui' === $filters['dossiersMultiples'])) {
            $qb->andWhere('EXISTS ('.$queryDossiersMultiples.')');
        } elseif (isset($filters['dossiersMultiples']) && 'non' === $filters['dossiersMultiples']) {
            $qb->andWhere('NOT EXISTS ('.$queryDossiersMultiples.')');
        }

        if ($user->isSuperAdmin()) {
            // pas de restrictions pour les SA
        } elseif ($user->isTerritoryAdmin()) {
            $qb->andWhere('s.territory IN (:territories)')->setParameter('territories', $user->getPartnersTerritories());
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
            $qb->andWhere('LOWER(s.adresseOccupant) LIKE :adresse');
            $qb->setParameter('adresse', '%'.$filters['adresse'].'%');
        }

        if (!empty($filters['zones'])) {
            $connection = $this->entityManager->getConnection();
            $params = $zonesParams = [];
            foreach ($filters['zones'] as $zoneId) {
                $zoneId = (int) $zoneId;
                $zonesParams[] = ':zone_'.$zoneId;
                $params['zone_'.$zoneId] = $zoneId;
            }
            $sql = '
                SELECT DISTINCT s2.id
                FROM signalement s2
                JOIN zone z ON z.id IN ('.implode(',', $zonesParams).')
                WHERE z.territory_id = s2.territory_id
                AND ST_Contains(
                    z.area,
                    Point(
                        JSON_EXTRACT(s2.geoloc, \'$.lng\'),
                        JSON_EXTRACT(s2.geoloc, \'$.lat\')
                    )
                ) = 1
            ';
            $stmt = $connection->prepare($sql);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }

            $zonesSignalements = $stmt->executeQuery()->fetchAllAssociative();

            if (!empty($zonesSignalements)) {
                $qb->andWhere('s.id IN (:zonesSignalements)')
                   ->setParameter('zonesSignalements', $zonesSignalements);
            } else {
                $qb->andWhere('s.id IS NULL');
            }
        }

        if (!empty($filters['cities'])) {
            foreach ($filters['cities'] as $city) {
                if (isset(CommuneHelper::COMMUNES_ARRONDISSEMENTS[$city])) {
                    $filters['cities'] = array_merge($filters['cities'], CommuneHelper::COMMUNES_ARRONDISSEMENTS[$city]);
                }
            }
            $qb->andWhere('s.villeOccupant IN (:cities) OR s.cpOccupant IN (:cities)')
                ->setParameter('cities', $filters['cities']);
        }
        if (!empty($filters['territories'])) {
            $qb->andWhere('s.territory IN (:territories)')
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
            // TODO
        }

        return $qb;
    }
}
