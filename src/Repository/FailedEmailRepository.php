<?php

namespace App\Repository;

use App\Entity\FailedEmail;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<FailedEmail>
 *
 * @method FailedEmail|null find($id, $lockMode = null, $lockVersion = null)
 * @method FailedEmail|null findOneBy(array $criteria, array $orderBy = null)
 * @method FailedEmail[]    findAll()
 * @method FailedEmail[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class FailedEmailRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FailedEmail::class);
    }
}
