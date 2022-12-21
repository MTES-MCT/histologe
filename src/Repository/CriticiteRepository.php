<?php

namespace App\Repository;

use App\Entity\Criticite;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Criticite|null find($id, $lockMode = null, $lockVersion = null)
 * @method Criticite|null findOneBy(array $criteria, array $orderBy = null)
 * @method Criticite[]    findAll()
 * @method Criticite[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CriticiteRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Criticite::class);
    }
}
