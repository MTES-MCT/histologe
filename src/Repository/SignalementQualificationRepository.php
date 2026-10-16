<?php

namespace App\Repository;

use App\Entity\SignalementQualification;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<SignalementQualification>
 *
 * @method SignalementQualification|null find($id, $lockMode = null, $lockVersion = null)
 * @method SignalementQualification|null findOneBy(array<string, mixed> $criteria, array<string, mixed>|null $orderBy = null)
 * @method SignalementQualification[]    findAll()
 * @method SignalementQualification[]    findBy(array<string, mixed> $criteria, array<string, mixed>|null $orderBy = null, $limit = null, $offset = null)
 */
class SignalementQualificationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SignalementQualification::class);
    }
}
