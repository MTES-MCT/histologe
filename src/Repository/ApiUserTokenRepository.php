<?php

namespace App\Repository;

use App\Entity\ApiUserToken;
use App\Repository\Behaviour\EntityCleanerRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ApiUserToken>
 */
class ApiUserTokenRepository extends ServiceEntityRepository implements EntityCleanerRepositoryInterface
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

    /**
     * @throws \DateMalformedStringException
     */
    public function cleanOlderThan(string $period = ApiUserToken::CLEAN_EXPIRATION_PERIOD): int
    {
        $qb = $this->createQueryBuilder('a');
        $qb->delete()
            ->andWhere('a.expiresAt < :now')
            ->setParameter('now', new \DateTimeImmutable($period));

        return $qb->getQuery()->execute();
    }
}
