<?php

namespace App\Repository\Query\Signalement;

use App\Entity\Enum\Qualification;
use App\Entity\Enum\QualificationStatus;
use App\Entity\Signalement;
use App\Entity\SignalementQualification;
use Doctrine\ORM\EntityManagerInterface;

class SignalementQualificationQuery
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * @param array<int, QualificationStatus>|null $statuses
     *
     * @return array<int, Signalement>
     */
    public function findSignalementsByQualification(Qualification $qualification, ?array $statuses = null, ?bool $isPostVisite = null): array
    {
        $queryBuilder = $this->entityManager->createQueryBuilder()->from(SignalementQualification::class, 'sq')
            ->select('s.id')
            ->innerJoin('sq.signalement', 's')
            ->where('sq.qualification LIKE :qualification')
            ->setParameter('qualification', $qualification);

        if (!empty($statuses)) {
            $queryBuilder
                ->andWhere('sq.status IN (:statuses)')
                ->setParameter('statuses', $statuses);
        }
        if (true === $isPostVisite) {
            $queryBuilder
                ->andWhere('sq.isPostVisite = :isPostVisite')
                ->setParameter('isPostVisite', $isPostVisite);
        } elseif (false === $isPostVisite) {
            $queryBuilder
                ->andWhere('sq.isPostVisite = :isPostVisite OR sq.isPostVisite IS NULL')
                ->setParameter('isPostVisite', $isPostVisite);
        }

        $queryBuilder->distinct();

        return $queryBuilder->getQuery()->getResult();
    }
}
