<?php

namespace App\Repository;

use App\Entity\Tag;
use App\Entity\Territory;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Tag|null find($id, $lockMode = null, $lockVersion = null)
 * @method Tag|null findOneBy(array $criteria, array $orderBy = null)
 * @method Tag[]    findAll()
 * @method Tag[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TagRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Tag::class);
    }

    // /**
    //  * @return Tags[] Returns an array of Tags objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('t.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    public function findAllActive(Territory|null $territory = null)
    {
        $qb = $this->createQueryBuilder('t')
            ->andWhere('t.isArchive != 1')
            ->indexBy('t', 't.id');
        if ($territory) {
            $qb->andWhere('t.territory = :territory')->setParameter('territory', $territory);
        }

        return $qb->getQuery()
            ->getResult();
    }
}
