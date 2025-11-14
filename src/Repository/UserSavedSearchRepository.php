<?php

namespace App\Repository;

use App\Entity\User;
use App\Entity\UserSavedSearch;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<UserSavedSearch>
 */
class UserSavedSearchRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserSavedSearch::class);
    }

    public function countForUser(User $user): int
    {
        return (int) $this->createQueryBuilder('s')
            ->select('COUNT(s.id)')
            ->where('s.user = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @return UserSavedSearch[]
     */
    public function findByUser(User $user): array
    {
        return $this->createQueryBuilder('s')
            ->where('s.user = :user')
            ->orderBy('s.createdAt', 'DESC')
            ->setParameter('user', $user)
            ->getQuery()
            ->getResult();
    }

    public function findAllForUserArray(User $user): array
    {
        $qb = $this->createQueryBuilder('uss')
            ->select('uss.id, uss.name, uss.params, uss.createdAt, uss.updatedAt')
            ->where('uss.user = :user')
            ->setParameter('user', $user)
            ->orderBy('uss.createdAt', 'DESC');

        $results = $qb->getQuery()->getArrayResult();

        foreach ($results as &$result) {
            if (is_string($result['params'])) {
                $result['params'] = json_decode($result['params'], true);
            }
        }

        return $results;
    }
}
