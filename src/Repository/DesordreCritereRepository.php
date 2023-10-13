<?php

namespace App\Repository;

use App\Entity\DesordreCritere;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<DesordreCritere>
 *
 * @method DesordreCritere|null find($id, $lockMode = null, $lockVersion = null)
 * @method DesordreCritere|null findOneBy(array $criteria, array $orderBy = null)
 * @method DesordreCritere[]    findAll()
 * @method DesordreCritere[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class DesordreCritereRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DesordreCritere::class);
    }
}
