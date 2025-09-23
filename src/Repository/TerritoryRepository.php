<?php

namespace App\Repository;

use App\Entity\Territory;
use App\Service\ListFilters\SearchTerritory;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\Query\QueryException;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Territory>
 *
 * @method Territory|null find($id, $lockMode = null, $lockVersion = null)
 * @method Territory|null findOneBy(array $criteria, array $orderBy = null)
 * @method Territory[]    findAll()
 * @method Territory[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TerritoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Territory::class);
    }

    /**
     * @return array<int, Territory>
     *
     * @throws QueryException
     */
    public function findAllList(): array
    {
        $qb = $this->createQueryBuilder('t')
            ->where('t.isActive = 1');

        return $qb->indexBy('t', 't.id')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return array<string, Territory>
     */
    public function findAllIndexedByZip(): array
    {
        $qb = $this->createQueryBuilder('t');

        return $qb->indexBy('t', 't.zip')
            ->getQuery()
            ->getResult();
    }

    /**
     * @throws NonUniqueResultException
     * @throws NoResultException
     */
    public function countAll(): int
    {
        $qb = $this->createQueryBuilder('t');
        $qb->select('COUNT(t.id)')
            ->where('t.isActive = 1');

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * @return array<int, Territory>
     */
    public function findAllWithAutoAffectationRules(): array
    {
        $qb = $this->createQueryBuilder('t')
            ->innerJoin('t.autoAffectationRules', 'aar')
            ->addSelect('aar')
            ->orderBy('t.zip', 'ASC');

        return $qb->getQuery()->getResult();
    }

    /**
     * @return Paginator<Territory>
     */
    public function findFilteredPaginated(SearchTerritory $searchTerritory, int $maxResult): Paginator
    {
        $qb = $this->createQueryBuilder('t');
        $qb->select('t');

        if (!empty($searchTerritory->getOrderType())) {
            [$orderField, $orderDirection] = explode('-', $searchTerritory->getOrderType());
            $qb->orderBy($orderField, $orderDirection);
        } else {
            $qb->orderBy('t.zip', 'ASC');
        }

        if ($searchTerritory->getQueryName()) {
            $qb->andWhere('LOWER(t.name) LIKE :queryName OR t.zip LIKE :queryName');
            $qb->setParameter('queryName', '%'.strtolower($searchTerritory->getQueryName()).'%');
        }
        if (null !== $searchTerritory->getIsActive()) {
            $qb->andWhere('t.isActive = :isActive');
            $qb->setParameter('isActive', $searchTerritory->getIsActive());
        }

        $firstResult = ($searchTerritory->getPage() - 1) * $maxResult;
        $qb->setFirstResult($firstResult)->setMaxResults($maxResult);

        return new Paginator($qb->getQuery());
    }

    /**
     * @return array<int, Territory>
     */
    public function findAllWithGridFile(): array
    {
        $qb = $this->createQueryBuilder('t')
            ->where('t.grilleVisiteFilename IS NOT NULL')
            ->andWhere('t.isGrilleVisiteDisabled = 0');

        return $qb->getQuery()->getResult();
    }
}
