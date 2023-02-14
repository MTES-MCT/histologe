<?php

namespace App\Repository;

use App\Entity\Territory;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
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

    public function findAllList()
    {
        $qb = $this->createQueryBuilder('t')
            ->select('PARTIAL t.{id,name,zip}')
            ->where('t.isActive = 1');

        return $qb->indexBy('t', 't.id')
            ->getQuery()
            ->getResult();
    }

    public function countAll(): int
    {
        $qb = $this->createQueryBuilder('t');
        $qb->select('COUNT(t.id)')
            ->where('t.isActive = 1');

        return $qb->getQuery()
            ->getSingleScalarResult();
    }
}
