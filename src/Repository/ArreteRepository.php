<?php

namespace App\Repository;

use App\Entity\Arrete;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Arrete>
 */
class ArreteRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Arrete::class);
    }

    /**
     * @return Arrete[]
     */
    public function findByBanId(string $banId): array
    {
        return $this->createQueryBuilder('a')
            ->join('a.address', 'addr')
            ->where('addr.banId = :banId')
            ->setParameter('banId', $banId)
            ->getQuery()
            ->getResult();
    }
}
