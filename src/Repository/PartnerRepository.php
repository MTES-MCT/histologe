<?php

namespace App\Repository;

use App\Entity\Affectation;
use App\Entity\Enum\PartnerType;
use App\Entity\Enum\Qualification;
use App\Entity\Partner;
use App\Entity\Signalement;
use App\Entity\Territory;
use App\Service\ListFilters\SearchArchivedPartner;
use App\Service\ListFilters\SearchPartner;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Exception;
use Doctrine\ORM\Query\QueryException;
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
            $searchPartner->getPage(),
            $maxResult,
            $searchPartner->getTerritory(),
            $searchPartner->getPartnerType(),
            $searchPartner->getQueryPartner(),
            $searchPartner->getOrderType(),
        );
    }

    public function getPartners(
        int $page,
        int $maxResult,
        ?Territory $territory,
        ?PartnerType $type,
        ?string $filterTerms,
        ?string $orderType = null,
    ): Paginator {
        $queryBuilder = $this->getPartnersQueryBuilder($territory);
        $queryBuilder->addSelect('z')
            ->leftJoin('p.zones', 'z')
            ->leftJoin('p.excludedZones', 'ez');

        if (!empty($type)) {
            $queryBuilder
                ->andWhere('p.type = :type')
                ->setParameter('type', $type);
        }

        if (!empty($filterTerms)) {
            $queryBuilder
                ->andWhere('LOWER(p.nom) LIKE :usersterms
                OR LOWER(p.email) LIKE :usersterms');
            $queryBuilder
                ->setParameter('usersterms', '%'.strtolower($filterTerms).'%');
        }

        if (!empty($orderType)) {
            [$orderField, $orderDirection] = explode('-', $orderType);
            $queryBuilder->orderBy($orderField, $orderDirection);
        } else {
            $queryBuilder->orderBy('p.nom', 'ASC');
        }

        $firstResult = ($page - 1) * $maxResult;
        $queryBuilder->setFirstResult($firstResult)->setMaxResults($maxResult);

        $paginator = new Paginator($queryBuilder->getQuery(), false);

        return $paginator;
    }

    /**
     * @throws QueryException
     */
    public function findAllList(?Territory $territory = null)
    {
        $qb = $this->createQueryBuilder('p')
            ->where('p.isArchive != 1')
            ->orderBy('p.nom', 'ASC');
        if ($territory) {
            $qb->andWhere('p.territory = :territory')->setParameter('territory', $territory);
        }

        return $qb->indexBy('p', 'p.id')
            ->getQuery()
            ->getResult();
    }

    public function findAllWithoutTerritory()
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
     * @throws Exception
     */
    public function findByLocalization(Signalement $signalement, bool $affected = true): array
    {
        $operator = $affected ? 'IN' : 'NOT IN';

        $subquery = $this->getEntityManager()->getRepository(Affectation::class)->createQueryBuilder('a')
        ->select('IDENTITY(a.partner)')
        ->where('a.signalement = :signalement')
        ->setParameter('signalement', $signalement);

        $affectedPartners = $subquery->getQuery()->getSingleColumnResult();
        $conn = $this->getEntityManager()->getConnection();
        $params = [
            'territory' => $signalement->getTerritory()->getId(),
            'insee' => '%'.$signalement->getInseeOccupant().'%',
            'lng' => $signalement->getGeoloc()['lng'] ?? 'notInZone',
            'lat' => $signalement->getGeoloc()['lat'] ?? 'notInZone',
        ];
        $clauseSubquery = '';
        if (\count($affectedPartners) || 'IN' == $operator) {
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
                AND (p.insee LIKE :insee OR p.insee LIKE \'%[]%\' OR p.insee LIKE \'%[""]%\')
                AND (z.id IS NULL OR ST_Contains(ST_GeomFromText(z.area), Point(:lng, :lat)))
                AND (ez.id IS NULL OR NOT ST_Contains(ST_GeomFromText(ez.area), Point(:lng, :lat)))
                '.$clauseSubquery.'
                ORDER BY p.nom ASC';

        $resultSet = $conn->executeQuery($sql, $params);

        return $resultSet->fetchAllAssociative();
    }

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
}
