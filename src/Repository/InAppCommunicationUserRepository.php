<?php

namespace App\Repository;

use App\Entity\InAppCommunicationUser;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<InAppCommunicationUser>
 */
class InAppCommunicationUserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, InAppCommunicationUser::class);
    }
}
