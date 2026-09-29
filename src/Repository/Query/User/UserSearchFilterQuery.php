<?php

namespace App\Repository\Query\User;

use App\Entity\Signalement;
use App\Entity\User;
use App\Entity\UserSearchFilter;
use Doctrine\ORM\EntityManagerInterface;

class UserSearchFilterQuery
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function countForUser(User $user): int
    {
        return (int) $this->entityManager->createQueryBuilder()->from(Signalement::class, 's')
            ->select('COUNT(s.id)')
            ->where('s.user = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @return array<array<string, mixed>>
     */
    public function findAllForUserArray(User $user): array
    {
        $qb = $this->entityManager->createQueryBuilder()->from(UserSearchFilter::class, 'uss')
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
