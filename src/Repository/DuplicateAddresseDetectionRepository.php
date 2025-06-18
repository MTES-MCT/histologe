<?php

namespace App\Repository;

use App\Entity\DuplicateAddresseDetection;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<DuplicateAddresseDetection>
 */
class DuplicateAddresseDetectionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DuplicateAddresseDetection::class);
    }
}
