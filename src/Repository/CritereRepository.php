<?php

namespace App\Repository;

use App\Entity\Critere;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Critere|null find($id, $lockMode = null, $lockVersion = null)
 * @method Critere|null findOneBy(array $criteria, array $orderBy = null)
 * @method Critere[]    findAll()
 * @method Critere[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CritereRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Critere::class);
    }

    // /**
    //  * @return Critere[] Returns an array of Critere objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('c.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */
    /**
     * @throws NonUniqueResultException
     * @throws NoResultException
     */
    public function getMaxScore()
    {
        return $this->createQueryBuilder('c')
            ->select('SUM(c.coef)')
            ->where('c.isArchive != 1')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function findAllList()
    {
        return $this->createQueryBuilder('c')
            ->select('PARTIAL c.{id,label}')
            ->where('c.isArchive != 1')
            ->indexBy('c', 'c.id')
            ->getQuery()
            ->getResult();
    }

    /*
    public function findOneBySomeField($value): ?Critere
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
