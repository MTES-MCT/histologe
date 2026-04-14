<?php

namespace App\Repository\Query\Statistics;

use App\Entity\Enum\CreationSource;
use App\Entity\Enum\DesordreCritereZone;
use App\Entity\Enum\SignalementStatus;
use App\Entity\Signalement;
use App\Entity\Territory;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;

class CountStatisticsQuery
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * @throws NonUniqueResultException
     * @throws NoResultException
     */
    public function countImported(?Territory $territory = null, ?User $user = null): int
    {
        $qb = $this->entityManager->createQueryBuilder()
            ->from(Signalement::class, 's')
            ->select('COUNT(s.id)')
            ->andWhere('s.statut NOT IN (:statutList)')
            ->setParameter('statutList', SignalementStatus::excludedStatuses())
            ->andWhere('s.isImported = 1');

        if (null !== $territory) {
            $qb->andWhere('s.territory = :territory')->setParameter('territory', $territory);
        }

        if ($user && !$user->isSuperAdmin()) {
            $qb->innerJoin('s.affectations', 'a')
                ->innerJoin('a.partner', 'partner')
                ->andWhere('partner IN (:partners)')
                ->setParameter('partners', $user->getPartners());
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * @throws NonUniqueResultException
     * @throws NoResultException
     */
    public function countValidated(bool $removeImported = false): int
    {
        $notStatus = array_merge([SignalementStatus::NEED_VALIDATION], SignalementStatus::excludedStatuses());
        $qb = $this->entityManager->createQueryBuilder()
            ->from(Signalement::class, 's')
            ->select('COUNT(s.id)')
            ->andWhere('s.statut NOT IN (:notStatus)')
            ->setParameter('notStatus', $notStatus);

        if ($removeImported) {
            $qb->andWhere('s.isImported IS NULL OR s.isImported = 0');
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * @throws NonUniqueResultException
     * @throws NoResultException
     */
    public function countClosed(bool $removeImported = false): int
    {
        $qb = $this->entityManager->createQueryBuilder()
            ->from(Signalement::class, 's')
            ->select('COUNT(s.id)')
            ->andWhere('s.statut = :closedStatus')
            ->setParameter('closedStatus', SignalementStatus::CLOSED);

        if ($removeImported) {
            $qb->andWhere('s.isImported IS NULL OR s.isImported = 0');
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * @throws NonUniqueResultException
     * @throws NoResultException
     */
    public function countRefused(): int
    {
        $qb = $this->entityManager->createQueryBuilder()
            ->from(Signalement::class, 's')
            ->select('COUNT(s.id)')
            ->andWhere('s.statut = :refusedStatus')
            ->setParameter('refusedStatus', SignalementStatus::REFUSED)
            ->andWhere('s.isImported IS NULL OR s.isImported = 0');

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function countByTerritory(bool $removeImported = false): array
    {
        $qb = $this->entityManager->createQueryBuilder()
            ->from(Signalement::class, 's')
            ->select('COUNT(s.id) AS count, t.zip, t.name, t.id')
            ->leftJoin('s.territory', 't')
            ->where('s.statut NOT IN (:statutList)')
            ->setParameter('statutList', SignalementStatus::excludedStatuses());

        if ($removeImported) {
            $qb->andWhere('s.isImported IS NULL OR s.isImported = 0');
        }

        $qb->groupBy('t.id');

        return $qb->getQuery()->getResult();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function countCritereByZone(?Territory $territory, ?int $year): array
    {
        $qb = $this->entityManager->createQueryBuilder()
            ->from(Signalement::class, 's')
            ->select('SUM(CASE WHEN c.type = :batiment THEN 1 ELSE 0 END) AS critere_batiment_count')
            ->addSelect('SUM(CASE WHEN c.type = :logement THEN 1 ELSE 0 END) AS critere_logement_count')
            ->addSelect('SUM(CASE WHEN dc.zoneCategorie = :batimentString THEN 1 ELSE 0 END) AS desordrecritere_batiment_count')
            ->addSelect('SUM(CASE WHEN dc.zoneCategorie = :logementString THEN 1 ELSE 0 END) AS desordrecritere_logement_count')
            ->leftJoin('s.criticites', 'criticites')
            ->leftJoin('criticites.critere', 'c')
            ->leftJoin('s.desordrePrecisions', 'dp')
            ->leftJoin('dp.desordreCritere', 'dc')
            ->setParameter('batiment', 1)
            ->setParameter('logement', 2)
            ->setParameter('batimentString', 'BATIMENT')
            ->setParameter('logementString', 'LOGEMENT');

        $qb->andWhere('s.isImported IS NULL OR s.isImported = 0');

        if ($territory) {
            $qb->andWhere('s.territory = :territory')->setParameter('territory', $territory);
        }
        if ($year) {
            $qb->andWhere('YEAR(s.createdAt) = :year')->setParameter('year', $year);
        }

        return $qb->getQuery()->getSingleResult();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function countByDesordresCriteres(
        ?Territory $territory,
        ?int $year,
        ?DesordreCritereZone $desordreCritereZone = null,
    ): array {
        $qb = $this->entityManager->createQueryBuilder()
            ->from(Signalement::class, 's')
            ->select('COUNT(s.id) AS count, desordreCriteres.labelCritere')
            ->leftJoin('s.desordrePrecisions', 'dp')
            ->leftJoin('dp.desordreCritere', 'desordreCriteres')
            ->where('s.statut NOT IN (:statutList)')
            ->setParameter('statutList', SignalementStatus::excludedStatuses())
            ->andWhere('s.creationSource = :creationSource')
            ->setParameter('creationSource', CreationSource::FORM_USAGER_V2);

        if ($territory) {
            $qb->andWhere('s.territory = :territory')->setParameter('territory', $territory);
        }
        if ($year) {
            $qb->andWhere('YEAR(s.createdAt) = :year')->setParameter('year', $year);
        }

        if ($desordreCritereZone) {
            $qb->andWhere('desordreCriteres.zoneCategorie = :desordreCritereZone')
                ->setParameter('desordreCritereZone', $desordreCritereZone);
        }

        $qb->groupBy('desordreCriteres.labelCritere')
            ->orderBy('count', 'DESC')
            ->setMaxResults(5);

        return $qb->getQuery()->getResult();
    }

    /**
     * @param array<int, Territory> $territories
     */
    public function countSignalementUsagerAbandonProcedure(array $territories): ?int
    {
        $qb = $this->entityManager->createQueryBuilder()
            ->from(Signalement::class, 's')
            ->select('COUNT(s.id)')
            ->where('s.statut IN (:statutList)')
            ->andWhere('s.isUsagerAbandonProcedure = 1')
            ->setParameter('statutList', [SignalementStatus::ACTIVE]);

        if (\count($territories)) {
            $qb->andWhere('s.territory IN (:territories)')->setParameter('territories', $territories);
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }
}
