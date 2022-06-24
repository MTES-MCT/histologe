<?php

namespace App\Repository;

use App\Entity\Situation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Situation|null find($id, $lockMode = null, $lockVersion = null)
 * @method Situation|null findOneBy(array $criteria, array $orderBy = null)
 * @method Situation[]    findAll()
 * @method Situation[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SituationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Situation::class);
    }

    // /**
    //  * @return Situation[] Returns an array of Situation objects
    //  */

    public function findAllActive()
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.isActive = true')
            ->leftJoin('s.criteres', 'criteres', 'WITH', 'criteres.isArchive != 1')
            ->leftJoin('criteres.criticites', 'criticites', 'WITH', 'criticites.isArchive != 1')
            ->addSelect('criteres')
            ->addSelect('criticites')
            ->orderBy('s.id', 'ASC')
            ->getQuery()
            ->getResult();
    }


    public function findAllWithCritereAndCriticite()
    {
        return $this->createQueryBuilder('s')
            ->where('s.isArchive != 1')
            ->andWhere('s.isActive = 1')
            ->leftJoin('s.criteres', 'criteres', 'WITH', 'criteres.isArchive != 1')
            ->leftJoin('App\Entity\Criticite', 'criticites', 'WITH', 'criticites.isArchive != 1')
            ->addSelect('criteres')
            ->addSelect('criticites')
            ->orderBy('s.isActive', 'DESC')
            ->getQuery()
            ->getResult();
    }


    /*
    public function findOneBySomeField($value): ?Situation
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
