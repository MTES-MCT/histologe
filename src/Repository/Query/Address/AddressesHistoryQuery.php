<?php

namespace App\Repository\Query\Address;

use App\Dto\Request\Signalement\AddressesHistorySearchQuery;
use App\Entity\Address;
use App\Entity\Arrete;
use App\Entity\Commune;
use App\Entity\Enum\SignalementStatus;
use App\Entity\Signalement;
use App\Entity\Territory;
use App\Entity\User;
use App\Utils\Address\CommuneHelper;
use Doctrine\DBAL\ArrayParameterType;
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
     * @return array<int, mixed>
     */
    public function findAllList(?Territory $territory = null): array
    {
        $qb = $this->entityManager->createQueryBuilder()
            ->from(Address::class, 'a')
            ->select('a.id, CONCAT_WS(\' \', a.housenumber, a.street) as address')
            ->orderBy('a.street', 'ASC')
            ->addOrderBy('a.housenumber', 'ASC');

        if ($territory) {
            $qb->andWhere('a.territory = :territory')
                ->setParameter('territory', $territory);
        }

        return $qb->getQuery()->getArrayResult();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function findAddressesWithHistory(
        User $user,
        ?AddressesHistorySearchQuery $addressesHistorySearchQuery = null,
    ): array {
        $page = null !== $addressesHistorySearchQuery && null !== $addressesHistorySearchQuery->getPage()
            ? $addressesHistorySearchQuery->getPage()
            : 1;
        $maxListPagination = AddressesHistorySearchQuery::MAX_LIST_PAGINATION;
        $firstResult = (max($page, 1) - 1) * $maxListPagination;

        $statusList = $this->getStatusList();

        // Step 1: Get paginated distinct address IDs
        $qbIds = $this->buildBaseQueryBuilder($user, $addressesHistorySearchQuery, $statusList);
        $qbIds->select('a.id', 'a.street', 'a.postCode', 'a.city')
            ->groupBy('a.id', 'a.street', 'a.postCode', 'a.city')
            ->orderBy('a.street', 'ASC')
            ->addOrderBy('a.postCode', 'ASC')
            ->addOrderBy('a.city', 'ASC')
            ->setFirstResult($firstResult)
            ->setMaxResults($maxListPagination);

        $addressIds = array_column($qbIds->getQuery()->getArrayResult(), 'id');

        if (empty($addressIds)) {
            return [];
        }

        // Step 2: Get all data for these addresses
        $qb = $this->entityManager->createQueryBuilder()
            ->from(Address::class, 'a')
            ->leftJoin('a.signalements', 's', 'WITH', 's.statut IN (:statusList)')
            ->leftJoin('s.bailleur', 'b')
            ->leftJoin('a.arretes', 'ar')
            ->select(
                'a.id AS addressId',
                'a.housenumber',
                'a.street',
                'a.postCode',
                'a.city',
                'a.cityCode',
                'a.point',
                'IDENTITY(a.territory) AS territoryId',
                's.id',
                's.uuid',
                's.createdAt',
                's.closedAt',
                's.reference',
                's.statut',
                's.profileDeclarant',
                's.geoloc',
                's.nomOccupant',
                's.prenomOccupant',
                's.nomProprio',
                's.isLogementSocial',
                'b.name AS bailleurName',
                's.denominationProprio',
                's.denominationSyndic',
                'ar.id AS arreteId',
                'ar.dateArrete',
                'ar.arreteType',
                'ar.dateMainLevee'
            )
            ->where('a.id IN (:addressIds)')
            ->setParameter('addressIds', $addressIds)
            ->setParameter('statusList', $statusList)
            ->orderBy('a.street', 'ASC')
            ->addOrderBy('a.postCode', 'ASC')
            ->addOrderBy('a.city', 'ASC')
            ->addOrderBy('s.createdAt', 'ASC')
            ->addOrderBy('ar.dateArrete', 'ASC');

        return $qb->getQuery()->getArrayResult();
    }

    public function countAddressesWithHistory(
        User $user,
        ?AddressesHistorySearchQuery $addressesHistorySearchQuery = null,
    ): int {
        $statusList = $this->getStatusList();
        $qb = $this->buildBaseQueryBuilder($user, $addressesHistorySearchQuery, $statusList);
        $qb->select('COUNT(DISTINCT a.id)');

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * @return array<SignalementStatus>
     */
    private function getStatusList(): array
    {
        return [
            SignalementStatus::ACTIVE,
            SignalementStatus::NEED_VALIDATION,
            SignalementStatus::CLOSED,
        ];
    }

    /**
     * @param array<SignalementStatus> $statusList
     */
    private function buildBaseQueryBuilder(
        User $user,
        ?AddressesHistorySearchQuery $addressesHistorySearchQuery,
        array $statusList,
    ): QueryBuilder {
        $qb = $this->entityManager->createQueryBuilder()
            ->from(Address::class, 'a')
            ->leftJoin('a.signalements', 's', 'WITH', 's.statut IN (:statusList)')
            ->leftJoin('a.arretes', 'ar')
            ->setParameter('statusList', $statusList);

        // Ensure we have at least one signalement or one arrete
        $qb->andWhere('s.id IS NOT NULL OR ar.id IS NOT NULL');

        $queryDossiersMultiples = 'SELECT 1 FROM '.Signalement::class.' s2
                WHERE s2.address = a
                AND s2.statut IN (:statusList)
                AND s2.id != s.id';
        if (!empty($addressesHistorySearchQuery) && null !== $addressesHistorySearchQuery->getDossiersMultiples()) {
            if ('oui' === $addressesHistorySearchQuery->getDossiersMultiples()) {
                $qb->andWhere('EXISTS ('.$queryDossiersMultiples.')');
            } elseif ('non' === $addressesHistorySearchQuery->getDossiersMultiples()) {
                $qb->andWhere('NOT EXISTS ('.$queryDossiersMultiples.')');
            }
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

        if (!empty($addressesHistorySearchQuery)) {
            $qb = $this->applyFilters($qb, $addressesHistorySearchQuery);
        }

        return $qb;
    }

    /**
     * @throws Exception
     */
    private function applyFilters(
        QueryBuilder $qb,
        ?AddressesHistorySearchQuery $addressesHistorySearchQuery = null,
    ): QueryBuilder {
        if (!empty($addressesHistorySearchQuery->getAdresse())) {
            $qb->andWhere("LOWER(CONCAT_WS(' ', a.housenumber, a.street)) LIKE :adresse");
            $qb->setParameter('adresse', '%'.strtolower($addressesHistorySearchQuery->getAdresse()).'%');
        }

        if (!empty($addressesHistorySearchQuery->getZone())) {
            $connection = $this->entityManager->getConnection();
            $sql = '
                SELECT DISTINCT a2.id
                FROM address a2
                JOIN zone z ON z.id = :zoneId
                WHERE z.territory_id = a2.territory_id
                AND a2.point IS NOT NULL
                AND ST_Contains(
                    z.area,
                    a2.point
                ) = 1
            ';
            $stmt = $connection->prepare($sql);
            $stmt->bindValue('zoneId', $addressesHistorySearchQuery->getZone());
            $zonesAddresses = $stmt->executeQuery()->fetchAllAssociative();

            if (!empty($zonesAddresses)) {
                $addressIds = array_column($zonesAddresses, 'id');
                $qb->andWhere('a.id IN (:zonesAddresses)')
                   ->setParameter('zonesAddresses', $addressIds);
            } else {
                // Aucune adresse trouvée dans cette zone, retourner aucun résultat
                $qb->andWhere('1 = 0');
            }
        }

        if (!empty($addressesHistorySearchQuery->getCommunes())) {
            $communes = [];
            $epcis = [];

            foreach ($addressesHistorySearchQuery->getCommunes() as $communeOrEpci) {
                // Vérifier si c'est un EPCI (préfixé par "EPCI: ")
                if (str_starts_with($communeOrEpci, 'EPCI : ')) {
                    $epcis[] = substr($communeOrEpci, 7); // Retirer le préfixe "EPCI : "
                } else {
                    $communes[] = $communeOrEpci;
                    // Gérer les arrondissements
                    if (isset(CommuneHelper::COMMUNES_ARRONDISSEMENTS[$communeOrEpci])) {
                        $communes = array_merge($communes, CommuneHelper::COMMUNES_ARRONDISSEMENTS[$communeOrEpci]);
                    }
                }
            }

            // Construire la condition de filtre
            if (!empty($communes) && !empty($epcis)) {
                // Si on a les deux, faire un OR entre communes et EPCIs
                // Utiliser une sous-requête pour les EPCIs
                $subQuery = 'SELECT DISTINCT a2.id FROM '.Address::class.' a2
                    INNER JOIN '.Commune::class.' c2 WITH a2.postCode = c2.codePostal AND a2.cityCode = c2.codeInsee
                    INNER JOIN c2.epci e2
                    WHERE e2.nom IN (:epcis)';

                $qb->andWhere('a.city IN (:cities) OR a.id IN ('.$subQuery.')')
                   ->setParameter('cities', $communes)
                   ->setParameter('epcis', $epcis);
            } elseif (!empty($communes)) {
                // Seulement des communes
                $qb->andWhere('a.city IN (:cities)')
                    ->setParameter('cities', $communes);
            } elseif (!empty($epcis)) {
                // Seulement des EPCIs - utiliser une sous-requête
                $subQuery = 'SELECT DISTINCT a2.id FROM '.Address::class.' a2
                    INNER JOIN '.Commune::class.' c2 WITH a2.postCode = c2.codePostal AND a2.cityCode = c2.codeInsee
                    INNER JOIN c2.epci e2
                    WHERE e2.nom IN (:epcis)';

                $qb->andWhere('a.id IN ('.$subQuery.')')
                   ->setParameter('epcis', $epcis);
            }
        }
        if (!empty($addressesHistorySearchQuery->getTerritoire())) {
            $qb->andWhere('a.territory IN (:territories)')
                ->setParameter('territories', $addressesHistorySearchQuery->getTerritoire());
        }

        if (!empty($addressesHistorySearchQuery->getNatureParc())) {
            if ('non_renseigne' === $addressesHistorySearchQuery->getNatureParc()) {
                $qb->andWhere('s.isLogementSocial IS NULL');
            } else {
                $natureParcValue = match ($addressesHistorySearchQuery->getNatureParc()) {
                    'public' => [1],
                    'privee' => [0],
                    'non_renseigne' => ['non_renseigne'],
                    default => null,
                };
                $qb->andWhere('s.isLogementSocial LIKE :natureParc')->setParameter('natureParc', $natureParcValue);
            }
        }

        if (!empty($addressesHistorySearchQuery->getBailleurOuSyndic())) {
            $qb->leftJoin('s.bailleur', 'b');
            $bailleurs = $addressesHistorySearchQuery->getBailleurOuSyndic();
            $conditions = [];
            foreach ($bailleurs as $index => $bailleur) {
                $paramName = 'bailleur'.$index;
                $conditions[] = "(s.nomProprio = :$paramName
                    OR s.denominationProprio = :$paramName
                    OR s.denominationSyndic = :$paramName
                    OR b.name = :$paramName)";
                $qb->setParameter($paramName, $bailleur);
            }
            $qb->andWhere(implode(' OR ', $conditions));
        }

        if (!empty($addressesHistorySearchQuery->getArreteTypes())) {
            // Utilise une sous-requête pour filtrer les adresses qui ont au moins un arrêté du type recherché
            // tout en chargeant tous les arrêtés de ces adresses
            $subQuery = 'SELECT IDENTITY(ar2.address) FROM '.Arrete::class.' ar2
                         WHERE ar2.arreteType IN (:arreteTypes)';
            $qb->andWhere('a.id IN ('.$subQuery.')')
                ->setParameter('arreteTypes', $addressesHistorySearchQuery->getArreteTypes());
        }

        return $qb;
    }

    /**
     * @return array<int, string>
     */
    public function findBailleursAndSyndics(User $user, ?Territory $territory = null): array
    {
        $conn = $this->entityManager->getConnection();

        $whereConditions = ['s.statut NOT IN (:statutList)'];
        $params = ['statutList' => array_map(static fn ($status) => $status->value, SignalementStatus::excludedStatuses())];
        $types = ['statutList' => ArrayParameterType::STRING];

        if (!$user->isSuperAdmin() && !$user->isTerritoryAdmin()) {
            $partnerIds = array_map(static fn ($p) => $p->getId(), $user->getPartners()->toArray());
            $whereConditions[] = 'EXISTS (
                SELECT 1 FROM affectation a
                WHERE a.signalement_id = s.id
                AND a.partner_id IN (:partnerIds)
            )';
            $params['partnerIds'] = $partnerIds;
            $types['partnerIds'] = ArrayParameterType::INTEGER;
        }

        if ($territory) {
            $whereConditions[] = 's.territory_id = :territoryId';
            $params['territoryId'] = $territory->getId();
        } elseif (!$user->isSuperAdmin()) {
            $territoryIds = array_map(static fn ($t) => $t->getId(), $user->getPartnersTerritories());
            $whereConditions[] = 's.territory_id IN (:territoryIds)';
            $params['territoryIds'] = $territoryIds;
            $types['territoryIds'] = ArrayParameterType::INTEGER;
        }

        $where = implode(' AND ', $whereConditions);

        // Utiliser une CTE pour filtrer une seule fois les signalements
        $sql = "
            WITH filtered_signalements AS (
                SELECT
                    s.id,
                    b.name as bailleur_name,
                    s.denomination_proprio,
                    s.denomination_syndic,
                    s.nom_proprio
                FROM signalement s
                LEFT JOIN bailleur b ON s.bailleur_id = b.id
                WHERE {$where}
            )
            SELECT DISTINCT unnested_name as name
            FROM (
                SELECT bailleur_name as unnested_name FROM filtered_signalements WHERE bailleur_name IS NOT NULL
                UNION ALL
                SELECT denomination_proprio FROM filtered_signalements WHERE denomination_proprio IS NOT NULL AND denomination_proprio != ''
                UNION ALL
                SELECT denomination_syndic FROM filtered_signalements WHERE denomination_syndic IS NOT NULL AND denomination_syndic != ''
                UNION ALL
                SELECT nom_proprio FROM filtered_signalements WHERE nom_proprio IS NOT NULL AND nom_proprio != ''
            ) all_names
            ORDER BY name ASC
        ";

        $results = $conn->executeQuery($sql, $params, $types)->fetchAllAssociative();

        return array_column($results, 'name');
    }
}
