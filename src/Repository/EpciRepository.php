<?php

namespace App\Repository;

use App\Entity\Epci;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Epci>
 *
 * @method Epci|null find($id, $lockMode = null, $lockVersion = null)
 * @method Epci|null findOneBy(array $criteria, array $orderBy = null)
 * @method Epci[]    findAll()
 * @method Epci[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class EpciRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Epci::class);
    }
}
