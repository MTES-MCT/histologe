<?php

namespace App\Repository;

use App\Entity\User;
use App\Entity\UserApiPermission;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<UserApiPermission>
 */
class UserApiPermissionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserApiPermission::class);
    }

    /**
     * @return UserApiPermission[]
     */
    public function getUserPermissions(User $user): array
    {
        return $this->createQueryBuilder('u')
            ->select('u', 'p', 't')
            ->leftJoin('u.partner', 'p')
            ->leftJoin('u.territory', 't')
            ->andWhere('u.user = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getResult();
    }
}
