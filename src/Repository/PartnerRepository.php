<?php

namespace App\Repository;

use App\Dto\CountPartner;
use App\Entity\Affectation;
use App\Entity\Enum\Qualification;
use App\Entity\Enum\UserStatus;
use App\Entity\Partner;
use App\Entity\Signalement;
use App\Entity\Territory;
use App\Entity\User;
use App\Entity\UserPartner;
use App\Service\ListFilters\SearchArchivedPartner;
use App\Service\ListFilters\SearchPartner;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Exception;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Partner|null find($id, $lockMode = null, $lockVersion = null)
 * @method Partner|null findOneBy(array $criteria, array $orderBy = null)
 * @method Partner[]    findAll()
 * @method Partner[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PartnerRepository extends ServiceEntityRepository
{
    public function __construct(
        private readonly TerritoryRepository $territoryRepository,
        ManagerRegistry $registry,
    ) {
        parent::__construct($registry, Partner::class);
    }

    public function getPartnersQueryBuilder(?Territory $territory): QueryBuilder
    {
        $queryBuilder = $this->createQueryBuilder('p')->where('p.isArchive != 1');

        if ($territory) {
            $queryBuilder->andWhere('p.territory = :territory')->setParameter('territory', $territory);
        }

        return $queryBuilder;
    }

    public function findFilteredPaginated(SearchPartner $searchPartner, int $maxResult): Paginator
    {
        return $this->getPartners(
            $maxResult,
            $searchPartner,
        );
    }

    public function getPartners(
        int $maxResult,
        SearchPartner $searchPartner,
    ): Paginator {
        $queryBuilder = $this->getPartnersQueryBuilder($searchPartner->getTerritoire());
        $queryBuilder->select('p', 'z', 'ez')
            ->leftJoin('p.zones', 'z')
            ->leftJoin('p.excludedZones', 'ez');

        $queryBuilder->addSelect(
            '(CASE
                WHEN (p.email IS NOT NULL AND p.email != \'\' AND p.emailNotifiable = 1) THEN 1
                WHEN EXISTS (
                    SELECT 1
                    FROM '.UserPartner::class.' up2
                    JOIN up2.user u2
                    WHERE up2.partner = p
                    AND u2.email IS NOT NULL
                    AND u2.statut LIKE \''.UserStatus::ACTIVE->value.'\'
                    AND u2.isMailingActive = 1
                ) THEN 1
                ELSE 0
            END) AS isNotifiable'
        );
        $user = $searchPartner->getUser();
        if (!$user->isSuperAdmin() && !$searchPartner->getTerritoire()) {
            $queryBuilder->andWhere('p.territory IN (:territories)')
                ->setParameter('territories', $user->getPartnersTerritories());
        }

        if ($searchPartner->getIsNotNotifiable()) {
            $queryBuilder->andHaving('isNotifiable = 0');
        }

        if ($searchPartner->getIsOnlyInterconnected()) {
            $queryBuilder->andWhere('p.isEsaboraActive = 1 or p.isIdossActive = 1');
        } elseif (false === $searchPartner->getIsOnlyInterconnected()) {
            $queryBuilder->andWhere('p.isEsaboraActive = 0 and p.isIdossActive = 0');
        }

        if (!empty($searchPartner->getPartnerType())) {
            $queryBuilder
                ->andWhere('p.type = :type')
                ->setParameter('type', $searchPartner->getPartnerType());
        }

        if (!empty($searchPartner->getQueryPartner())) {
            $queryBuilder
                ->andWhere('LOWER(p.nom) LIKE :usersterms
                OR LOWER(p.email) LIKE :usersterms');
            $queryBuilder
                ->setParameter('usersterms', '%'.strtolower($searchPartner->getQueryPartner()).'%');
        }

        if (!empty($searchPartner->getOrderType())) {
            [$orderField, $orderDirection] = explode('-', $searchPartner->getOrderType());
            $queryBuilder->orderBy($orderField, $orderDirection);
        } else {
            $queryBuilder->orderBy('p.nom', 'ASC');
        }

        $firstResult = ($searchPartner->getPage() - 1) * $maxResult;
        $queryBuilder->setFirstResult($firstResult)->setMaxResults($maxResult);

        $paginator = new Paginator($queryBuilder->getQuery());

        return $paginator;
    }

    /**
     * @param array<int, mixed> $territories
     */
    public function countPartnerNonNotifiables(array $territories): CountPartner
    {
        $queryBuilder = $this->createQueryBuilder('p')
        ->select('p.id');

        // Filtre sur les partenaires non notifiables
        $queryBuilder->addSelect(
            '(CASE
                WHEN (p.email IS NOT NULL AND p.email != \'\' AND p.emailNotifiable = 1) THEN 1
                WHEN EXISTS (
                    SELECT 1
                    FROM '.UserPartner::class.' up2
                    JOIN up2.user u2
                    WHERE up2.partner = p
                    AND u2.email IS NOT NULL
                    AND u2.statut LIKE \''.UserStatus::ACTIVE->value.'\'
                    AND u2.isMailingActive = 1
                ) THEN 1
                ELSE 0
            END) AS isNotifiable'
        );
        $queryBuilder->andHaving('isNotifiable = 0');

        $queryBuilder->andWhere('p.isArchive = 0');

        // Filtrer par territoires si précisé
        if (!empty($territories)) {
            $queryBuilder
                ->andWhere('p.territory IN (:territories)')
                ->setParameter('territories', $territories);
        }
        try {
            $count = count($queryBuilder->getQuery()->getSingleColumnResult());
        } catch (NonUniqueResultException) {
            $count = 0;
        }

        return new CountPartner((int) $count);
    }

    /**
     * @return array<string, Partner>
     */
    public function findAllList(?Territory $territory = null, ?User $user = null): array
    {
        $qb = $this->createQueryBuilder('p')
            ->where('p.isArchive != 1')
            ->orderBy('p.nom', 'ASC');
        if ($user && !$user->isSuperAdmin()) {
            $qb->andWhere('p.territory IN (:territories)')->setParameter('territories', $user->getPartnersTerritories());
        }
        if ($territory) {
            $qb->andWhere('p.territory = :territory')->setParameter('territory', $territory);
        }

        return $qb->indexBy('p', 'p.id')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return array<string, Partner>
     */
    public function findAllWithoutTerritory(): array
    {
        $qb = $this->createQueryBuilder('p')
            ->where('p.isArchive != 1')
            ->andWhere('p.territory IS NULL');

        return $qb->indexBy('p', 'p.id')
            ->getQuery()
            ->getResult();
    }

    public function findFilteredArchivedPaginated(SearchArchivedPartner $searchArchivedPartner, int $maxResult): Paginator
    {
        $queryBuilder = $this->createQueryBuilder('p');

        $isNoneTerritory = ('none' == $searchArchivedPartner->getTerritory());
        if ($isNoneTerritory) {
            $queryBuilder
                ->where('p.territory IS NULL');
        } else {
            $territory = $searchArchivedPartner->getTerritory() ? $this->territoryRepository->find($searchArchivedPartner->getTerritory()) : null;
            $builtOrCondition = '';
            if (empty($territory)) {
                $builtOrCondition .= ' OR p.territory IS NULL';
            }

            $queryBuilder
                ->where('p.isArchive = 1'.$builtOrCondition);

            if (!empty($territory)) {
                $queryBuilder
                    ->andWhere('p.territory = :territory')
                    ->setParameter('territory', $territory);
            }
        }

        $filterTerms = $searchArchivedPartner->getQueryArchivedPartner();
        if (!empty($filterTerms)) {
            $queryBuilder
                ->andWhere('LOWER(p.nom) LIKE :usersterms OR LOWER(p.email) LIKE :usersterms')
                ->setParameter('usersterms', '%'.strtolower($filterTerms).'%');
        }

        if (!empty($searchArchivedPartner->getOrderType())) {
            [$orderField, $orderDirection] = explode('-', $searchArchivedPartner->getOrderType());
            $queryBuilder->orderBy($orderField, $orderDirection);
        } else {
            $queryBuilder->orderBy('p.nom', 'ASC');
        }

        $firstResult = ($searchArchivedPartner->getPage() - 1) * $maxResult;
        $queryBuilder->setFirstResult($firstResult)->setMaxResults($maxResult);

        return new Paginator($queryBuilder->getQuery(), false);
    }

    /**
     * @return array<int, array<string, int|string>>
     *
     * @throws Exception
     */
    public function findByLocalization(Signalement $signalement, bool $affected = true): array
    {
        $queryData = $this->buildLocalizationQuery($signalement, $affected);

        $resultSet = $this->getEntityManager()->getConnection()->executeQuery(
            $queryData['sql'],
            $queryData['params']
        );

        return $resultSet->fetchAllAssociative();
    }

    /**
     * @return array<int, Partner>
     *
     * @throws Exception
     */
    public function findPartnersByLocalization(Signalement $signalement, bool $addAffectedPartner = false): array
    {
        $queryData = $this->buildLocalizationQuery($signalement, false); // Always use $affected = false

        $resultSet = $this->getEntityManager()->getConnection()->executeQuery(
            $queryData['sql'],
            $queryData['params']
        );
        $partnerIds = array_column($resultSet->fetchAllAssociative(), 'id');
        if ($addAffectedPartner) {
            $queryData = $this->buildLocalizationQuery($signalement, true);
            $resultSet = $this->getEntityManager()->getConnection()->executeQuery(
                $queryData['sql'],
                $queryData['params']
            );
            $partnerIds = array_merge($partnerIds, array_column($resultSet->fetchAllAssociative(), 'id'));
        }

        return $this->getEntityManager()->getRepository(Partner::class)->findBy(['id' => $partnerIds]);
    }

    /**
     * Builds the SQL query and parameters for localization search.
     *
     * @return array<int|string, mixed>
     *
     * @throws Exception
     */
    private function buildLocalizationQuery(Signalement $signalement, bool $affected): array
    {
        $operator = $affected ? 'IN' : 'NOT IN';

        $subquery = $this->getEntityManager()->getRepository(Affectation::class)->createQueryBuilder('a')
            ->select('IDENTITY(a.partner)')
            ->where('a.signalement = :signalement')
            ->setParameter('signalement', $signalement);

        $affectedPartners = $subquery->getQuery()->getSingleColumnResult();

        $params = [
            'territory' => $signalement->getTerritory()->getId(),
            'insee' => '%'.$signalement->getInseeOccupant().'%',
            'lng' => $signalement->getGeoloc()['lng'] ?? 'notInZone',
            'lat' => $signalement->getGeoloc()['lat'] ?? 'notInZone',
        ];

        $clauseSubquery = '';
        if (\count($affectedPartners) || 'IN' === $operator) {
            if (0 === \count($affectedPartners)) {
                $clauseSubquery = 'AND p.id '.$operator.' (null)';
            } else {
                $partnersParams = [];
                foreach ($affectedPartners as $key => $partner) {
                    $partnersParams[] = ':partner_'.$key;
                    $params['partner_'.$key] = $partner;
                }
                $clauseSubquery = 'AND p.id '.$operator.' ('.implode(',', $partnersParams).')';
            }
        }

        $sql = '
                SELECT p.id, p.nom as name
                FROM partner p
                LEFT JOIN partner_zone pz ON p.id = pz.partner_id
                LEFT JOIN zone z ON pz.zone_id = z.id
                LEFT JOIN partner_excluded_zone pez ON p.id = pez.partner_id
                LEFT JOIN zone ez ON pez.zone_id = ez.id
                WHERE p.is_archive = 0
                AND p.territory_id = :territory
                AND (
                    (
                        p.insee IS NOT NULL
                        AND p.insee != \'[]\'
                        AND p.insee != \'[""]\'
                        AND p.insee LIKE :insee
                    )
                    OR (
                        z.id IS NOT NULL
                        AND ST_Contains(ST_GeomFromText(z.area), Point(:lng, :lat))
                    )
                    OR (
                        (p.insee IS NULL OR p.insee LIKE \'[]\' OR p.insee LIKE \'[""]\' )
                        AND z.id IS NULL
                    )
                )
                AND (ez.id IS NULL OR NOT ST_Contains(ST_GeomFromText(ez.area), Point(:lng, :lat)))
                '.$clauseSubquery.'
                ORDER BY p.nom ASC';

        return [
            'sql' => $sql,
            'params' => $params,
        ];
    }

    /**
     * @return array<string, Partner>
     */
    public function findPartnersWithQualification(Qualification $qualification, ?Territory $territory)
    {
        $qb = $this->createQueryBuilder('p');
        $qb->andWhere('REGEXP(p.competence, :regexp) = true')
            ->setParameter('regexp', '(^'.$qualification->name.',)|(,'.$qualification->name.',)|(,'.$qualification->name.'$)|(^'.$qualification->name.'$)');
        if ($territory) {
            $qb->andWhere('p.territory = :territory')
                ->setParameter('territory', $territory);
        }

        return $qb->indexBy('p', 'p.id')
            ->getQuery()
            ->getResult();
    }

    public function getWithUserPartners(Partner $partner): Partner
    {
        return $this->createQueryBuilder('p')
        ->select('p', 'up', 'u')
        ->leftJoin('p.userPartners', 'up')
        ->leftJoin('up.user', 'u')
        ->where('p.id = :partner')
        ->setParameter('partner', $partner)
        ->getQuery()
        ->getOneOrNullResult();
    }
}
