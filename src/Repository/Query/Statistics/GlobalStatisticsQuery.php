<?php

namespace App\Repository\Query\Statistics;

use App\Entity\Enum\Qualification;
use App\Entity\Enum\QualificationStatus;
use App\Entity\Enum\SignalementStatus;
use App\Entity\Partner;
use App\Entity\Signalement;
use App\Entity\Territory;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\Query\QueryException;

class GlobalStatisticsQuery
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * @param ?ArrayCollection<int, Partner> $partners
     */
    public function countAll(
        ?Territory $territory,
        ?ArrayCollection $partners,
    ): int {
        $qb = $this->entityManager->createQueryBuilder()
            ->from(Signalement::class, 's')
            ->select('COUNT(s.id)');

        $qb->andWhere('s.statut NOT IN (:excludedStatuses)')
            ->setParameter('excludedStatuses', SignalementStatus::excludedStatuses());

        $qb->andWhere('s.isImported IS NULL OR s.isImported = 0');

        if ($territory) {
            $qb->andWhere('s.territory = :territory')->setParameter('territory', $territory);
        }
        if ($partners && $partners->count() > 0) {
            $qb->leftJoin('s.affectations', 'affectations')
                ->leftJoin('affectations.partner', 'partner')
                ->andWhere('partner IN (:partners)')
                ->setParameter('partners', $partners);
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * @param array<int, Territory>                $territories
     * @param ?ArrayCollection<int, Partner>       $partners
     * @param array<int, QualificationStatus>|null $qualificationStatuses
     *
     * @return array<int, array<string, mixed>>
     *
     * @throws QueryException
     */
    public function countByStatus(
        array $territories,
        ?ArrayCollection $partners,
        ?int $year = null,
        bool $removeImported = false,
        ?Qualification $qualification = null,
        ?array $qualificationStatuses = null,
        ?bool $keepArchivedSignalements = false,
    ): array {
        $qb = $this->entityManager->createQueryBuilder()
            ->from(Signalement::class, 's')
            ->select('COUNT(s.id) as count')
            ->addSelect('s.statut');

        if ($keepArchivedSignalements) {
            $qb->andWhere('s.statut NOT IN (:statutList)')
               ->setParameter('statutList', [SignalementStatus::DRAFT, SignalementStatus::DRAFT_ARCHIVED, SignalementStatus::INJONCTION_BAILLEUR, SignalementStatus::INJONCTION_CLOSED]);
        } else {
            $qb->andWhere('s.statut NOT IN (:statutList)')
               ->setParameter('statutList', SignalementStatus::excludedStatuses());
        }

        if ($removeImported) {
            $qb->andWhere('s.isImported IS NULL OR s.isImported = 0');
        }

        if (\count($territories)) {
            $qb->andWhere('s.territory IN (:territories)')->setParameter('territories', $territories);
        }
        if ($partners && $partners->count() > 0) {
            $qb->leftJoin('s.affectations', 'affectations')
                ->leftJoin('affectations.partner', 'partner')
                ->andWhere('partner IN (:partners)')
                ->setParameter('partners', $partners);
        }
        if ($year) {
            $qb->andWhere('YEAR(s.createdAt) = :year')->setParameter('year', $year);
        }

        if ($qualification) {
            $qb->innerJoin('s.signalementQualifications', 'sq')
                ->andWhere('sq.qualification = :qualification')
                ->setParameter('qualification', $qualification);

            if (!empty($qualificationStatuses)) {
                $qb->andWhere('sq.status IN (:statuses)')
                    ->setParameter('statuses', $qualificationStatuses);
            }
        }

        $qb->indexBy('s', 's.statut')->groupBy('s.statut');

        return $qb->getQuery()->getResult();
    }

    /**
     * @param ?ArrayCollection<int, Partner> $partners
     */
    public function getAverageCriticite(
        ?Territory $territory,
        ?ArrayCollection $partners,
    ): ?float {
        $qb = $this->entityManager->createQueryBuilder()
            ->from(Signalement::class, 's')
            ->select('AVG(s.score)');

        $qb->andWhere('s.isImported IS NULL OR s.isImported = 0');
        $qb->andWhere('s.statut NOT IN (:excludedStatuses)')->setParameter('excludedStatuses', SignalementStatus::excludedStatuses());

        if ($territory) {
            $qb->andWhere('s.territory = :territory')->setParameter('territory', $territory);
        }
        if ($partners && $partners->count() > 0) {
            $qb->leftJoin('s.affectations', 'affectations')
                ->leftJoin('affectations.partner', 'partner')
                ->andWhere('partner IN (:partners)')
                ->setParameter('partners', $partners);
        }

        return (float) $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * @param ?ArrayCollection<int, Partner> $partners
     *
     * @throws NonUniqueResultException
     * @throws NoResultException
     */
    public function getAverageDaysValidation(
        ?Territory $territory,
        ?ArrayCollection $partners,
    ): ?float {
        return $this->getAverageDayResult('validatedAt', $territory, $partners);
    }

    /**
     * @param ?ArrayCollection<int, Partner> $partners
     *
     * @throws NonUniqueResultException
     * @throws NoResultException
     */
    public function getAverageDaysClosure(
        ?Territory $territory,
        ?ArrayCollection $partners,
    ): ?float {
        return $this->getAverageDayResult('closedAt', $territory, $partners);
    }

    /**
     * @param ?ArrayCollection<int, Partner> $partners
     *
     * @throws NonUniqueResultException
     * @throws NoResultException
     */
    private function getAverageDayResult(
        string $field,
        ?Territory $territory,
        ?ArrayCollection $partners,
    ): ?float {
        $qb = $this->entityManager->createQueryBuilder()
            ->from(Signalement::class, 's')
            ->select('AVG(datediff(s.'.$field.', s.createdAt))');

        $qb->andWhere('s.'.$field.' IS NOT NULL');
        $qb->andWhere('s.statut NOT IN (:excludedStatuses)')->setParameter('excludedStatuses', SignalementStatus::excludedStatuses());
        $qb->andWhere('s.isImported IS NULL OR s.isImported = 0');

        if ($territory) {
            $qb->andWhere('s.territory = :territory')->setParameter('territory', $territory);
        }
        if ($partners && $partners->count() > 0) {
            $qb->leftJoin('s.affectations', 'affectations')
                ->leftJoin('affectations.partner', 'partner')
                ->andWhere('partner IN (:partners)')
                ->setParameter('partners', $partners);
        }

        return $qb->getQuery()->getSingleScalarResult();    // @phpstan-ignore-line
    }
}
