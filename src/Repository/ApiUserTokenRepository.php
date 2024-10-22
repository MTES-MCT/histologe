<?php

namespace App\Repository;

use App\Entity\ApiUserToken;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ApiUserToken>
 */
class ApiUserTokenRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ApiUserToken::class);
    }

    /**
     * @throws NonUniqueResultException
     */
    public function findValidUserToken(string $token): ?ApiUserToken
    {
        $qb = $this->createQueryBuilder('a');
        $qb->andWhere('a.token = :token')
            ->andWhere('a.expiresAt > :now')
            ->setParameter('token', $token)
            ->setParameter('now', new \DateTimeImmutable())
            ->setMaxResults(1);

        return $qb->getQuery()->getOneOrNullResult();
    }
}
