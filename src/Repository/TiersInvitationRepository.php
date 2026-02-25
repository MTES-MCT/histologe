<?php

namespace App\Repository;

use App\Entity\TiersInvitation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TiersInvitation>
 */
class TiersInvitationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TiersInvitation::class);
    }

    public function findOneByCodeAndToken(string $code, string $token): ?TiersInvitation
    {
        return $this->createQueryBuilder('ti')
            ->join('ti.signalement', 's')
            ->andWhere('s.codeSuivi = :code')
            ->andWhere('ti.token = :token')
            ->setParameter('code', $code)
            ->setParameter('token', $token)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
