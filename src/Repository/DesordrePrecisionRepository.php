<?php

namespace App\Repository;

use App\Entity\DesordrePrecision;
use App\Entity\Enum\AppContext;
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

    public function findWithCritereBySlug(string $slug, AppContext $appContext = AppContext::DEFAULT): ?DesordrePrecision
    {
        return $this->createQueryBuilder('dp')
            ->select('dp', 'dc', 'dps')
            ->andWhere('dp.desordrePrecisionSlug = :slug')
            ->andWhere('cat.appContext = :appContext')
            ->setParameter('slug', $slug)
            ->setParameter('appContext', $appContext)
            ->leftJoin('dp.desordreCritere', 'dc')
            ->leftJoin('dc.desordrePrecisions', 'dps')
            ->innerJoin('dc.desordreCategorie', 'dc')
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
}
