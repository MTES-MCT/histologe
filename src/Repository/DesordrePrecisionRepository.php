<?php

namespace App\Repository;

use App\Entity\DesordrePrecision;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<DesordrePrecision>
 *
 * @method DesordrePrecision|null find($id, $lockMode = null, $lockVersion = null)
 * @method DesordrePrecision|null findOneBy(array $criteria, array $orderBy = null)
 * @method DesordrePrecision[]    findAll()
 * @method DesordrePrecision[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class DesordrePrecisionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DesordrePrecision::class);
    }

    public function findWithCritereBySlug(string $slug): ?DesordrePrecision
    {
        return $this->createQueryBuilder('dp')
            ->select('dp', 'dc', 'dps')
            ->andWhere('dp.desordrePrecisionSlug = :slug')
            ->setParameter('slug', $slug)
            ->leftJoin('dp.desordreCritere', 'dc')
            ->leftJoin('dc.desordrePrecisions', 'dps')
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
}
