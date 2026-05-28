<?php

namespace App\Repository;

use App\Entity\JobEvent;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<JobEvent>
 *
 * @method JobEvent|null find($id, $lockMode = null, $lockVersion = null)
 * @method JobEvent|null findOneBy(array<string, mixed> $criteria, array<string, mixed>|null $orderBy = null)
 * @method JobEvent[]    findAll()
 * @method JobEvent[]    findBy(array<string, mixed> $criteria, array<string, mixed>|null $orderBy = null, $limit = null, $offset = null)
 */
class JobEventRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, JobEvent::class);
    }
}
