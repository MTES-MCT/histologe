<?php

namespace App\Repository;

use App\Entity\ApiUserToken;
use App\Entity\Enum\UserStatus;
use App\Entity\User;
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
        $qb->innerJoin('a.ownedBy', 'u')
            ->andWhere('u.roles LIKE :role_api')
            ->andWhere('u.statut = :statut_active')
            ->andWhere('a.token = :token')
            ->andWhere('a.expiresAt > :now')
            ->setParameter('role_api', '%'.User::ROLE_API_USER.'%')
            ->setParameter('statut_active', UserStatus::ACTIVE)
            ->setParameter('token', $token)
            ->setParameter('now', new \DateTimeImmutable())
            ->setMaxResults(1);

        return $qb->getQuery()->getOneOrNullResult();
    }
}
