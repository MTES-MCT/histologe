<?php

namespace App\Repository\Query\Statistics;

use App\Dto\StatisticsFilters;
use App\Entity\Enum\MotifCloture;
use App\Entity\Enum\SignalementStatus;
use App\Entity\Enum\TravauxMiseEnConformite;
use App\Entity\Signalement;
use App\Entity\Territory;
use Doctrine\ORM\EntityManagerInterface;

class MotifClotureStatisticsQuery
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly FilteredStatisticsQuery $filteredStatisticsQuery,
    ) {
    }

    public function countSignalementResolu(): int
    {
        $qb = $this->entityManager->createQueryBuilder()
            ->from(Signalement::class, 's')
            ->innerJoin('s.address', 'address')
            ->select('COUNT(s.id)')
            ->where('s.statut = :statut')
            ->setParameter('statut', SignalementStatus::CLOSED)
            ->andWhere('s.closedAt IS NOT NULL')
            ->andWhere('s.isImported IS NULL OR s.isImported = 0')
            ->andWhere('
                (s.motifCloture IN (:motifsClotureV1) AND s.travauxMiseEnConformite IS NULL) 
                OR 
                (s.motifCloture IN (:motifsClotureV2) AND s.travauxMiseEnConformite IN (:miseEnConformite))')
            ->setParameter('motifsClotureV1', [
                MotifCloture::TRAVAUX_FAITS_OU_EN_COURS,
                MotifCloture::RELOGEMENT_OCCUPANT,
                MotifCloture::INSALUBRITE,
                MotifCloture::RSD,
                MotifCloture::PERIL,
            ])
            ->setParameter('motifsClotureV2', MotifCloture::getListNeedTravauxPrecisions())
            ->setParameter('miseEnConformite', [TravauxMiseEnConformite::OUI, TravauxMiseEnConformite::EN_COURS])
        ;

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function countByMotifCloture(?Territory $territory, ?int $year, bool $removeImported = false): array
    {
        $qb = $this->entityManager->createQueryBuilder()
            ->from(Signalement::class, 's')
            ->select('COUNT(s.id) AS count, s.motifCloture')
            ->innerJoin('s.address', 'address')
            ->where('s.motifCloture IS NOT NULL')
            ->andWhere('s.motifCloture != \'0\'')
            ->andWhere('s.closedAt IS NOT NULL')
            ->andWhere('s.statut = :statut')
            ->setParameter('statut', SignalementStatus::CLOSED);

        if ($removeImported) {
            $qb->andWhere('s.isImported IS NULL OR s.isImported = 0');
        }

        if ($territory) {
            $qb->andWhere('address.territory = :territory')->setParameter('territory', $territory);
        }

        if ($year) {
            $qb->andWhere('YEAR(s.createdAt) = :year')->setParameter('year', $year);
        }

        $qb->groupBy('s.motifCloture');
        $qb->orderBy('s.motifCloture');

        return $qb->getQuery()->getResult();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function countByMotifClotureFiltered(StatisticsFilters $statisticsFilters): array
    {
        $qb = $this->entityManager->createQueryBuilder()
            ->from(Signalement::class, 's')
            ->select('COUNT(s.id) AS count, s.motifCloture')
            ->where('s.motifCloture IS NOT NULL')
            ->andWhere('s.motifCloture != \'0\'')
            ->andWhere('s.closedAt IS NOT NULL');

        $qb = $this->filteredStatisticsQuery->addFiltersToQueryBuilder($qb, $statisticsFilters);

        $qb->groupBy('s.motifCloture');

        return $qb->getQuery()->getResult();
    }
}
