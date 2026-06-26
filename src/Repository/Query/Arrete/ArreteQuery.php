<?php

namespace App\Repository\Query\Arrete;

use App\Entity\Arrete;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

class ArreteQuery
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function countForUser(User $user): int
    {
        $qb = $this->entityManager->createQueryBuilder()
        ->from(Arrete::class, 'a');
        $qb->select('COUNT(a.id)');
        $qb->innerJoin('a.address', 'address');

        if (!$user->isSuperAdmin()) {
            $qb->andWhere('address.territory IN (:territories)')->setParameter('territories', $user->getPartnersTerritories());
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }
}
