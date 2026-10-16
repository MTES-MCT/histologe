<?php

namespace App\Repository\Query\Signalement;

use App\Entity\Signalement;
use App\Entity\Territory;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\TransactionRequiredException;

class SignalementReferenceQuery
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function findMaxReferenceInjonction(): ?int
    {
        $qb = $this->entityManager->createQueryBuilder()->from(Signalement::class, 's')->select('MAX(s.referenceInjonction)');

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * @return array<string, string>|null
     *
     * @throws TransactionRequiredException
     * @throws NonUniqueResultException
     */
    public function findLastReferenceByTerritory(Territory $territory): ?array
    {
        $year = (new \DateTime())->format('Y');
        $queryBuilder = $this->entityManager->createQueryBuilder()->from(Signalement::class, 's')
            ->select('s.reference')
            ->addSelect("SUBSTRING_INDEX(s.reference, '-', 1) AS year")
            ->addSelect("CAST(SUBSTRING_INDEX(s.reference, '-', -1) AS SIGNED) AS reference_index")
            ->innerJoin('s.address', 'address')
            ->where('YEAR(s.createdAt) = :year')
            ->setParameter('year', $year)
            ->andWhere('address.territory = :territory')
            ->setParameter('territory', $territory)
            ->orderBy('reference_index', 'DESC')
            ->setMaxResults(1);

        return $queryBuilder
            ->getQuery()
            ->setLockMode(LockMode::PESSIMISTIC_WRITE)
            ->getOneOrNullResult();
    }
}
