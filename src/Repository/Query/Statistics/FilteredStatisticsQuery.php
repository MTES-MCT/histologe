<?php

namespace App\Repository\Query\Statistics;

use App\Dto\StatisticsFilters;
use App\Entity\Affectation;
use App\Entity\Commune;
use App\Entity\Enum\SignalementStatus;
use App\Entity\Signalement;
use App\Utils\Address\CommuneHelper;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\QueryBuilder;

class FilteredStatisticsQuery
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * @throws NonUniqueResultException
     * @throws NoResultException
     */
    public function countFiltered(StatisticsFilters $statisticsFilters): ?int
    {
        $qb = $this->entityManager->createQueryBuilder()
            ->from(Signalement::class, 's')
            ->select('COUNT(s.id)');

        $qb = $this->addFiltersToQueryBuilder($qb, $statisticsFilters);

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * @throws NonUniqueResultException
     * @throws NoResultException
     */
    public function getAverageCriticiteFiltered(StatisticsFilters $statisticsFilters): ?float
    {
        $qb = $this->entityManager->createQueryBuilder()
            ->from(Signalement::class, 's')
            ->select('AVG(s.score)');

        $qb->andWhere('s.score IS NOT NULL');

        $qb = $this->addFiltersToQueryBuilder($qb, $statisticsFilters);

        return (float) $qb->getQuery()->getSingleScalarResult();
    }

    public function addFiltersToQueryBuilder(QueryBuilder $qb, StatisticsFilters $filters): QueryBuilder
    {
        // Is the status defined?
        if ('' != $filters->getStatut() && 'all' != $filters->getStatut()) {
            $statutParameter = [];
            switch ($filters->getStatut()) {
                case 'new':
                    $statutParameter[] = SignalementStatus::NEED_VALIDATION;
                    break;
                case 'active':
                    $statutParameter[] = SignalementStatus::ACTIVE;
                    break;
                case 'closed':
                    $statutParameter[] = SignalementStatus::CLOSED;
                    break;
                default:
                    break;
            }
            // If we count the Refused status
            if ($filters->isCountRefused()) {
                $statutParameter[] = SignalementStatus::REFUSED;
            }
            // If we count the Archived status
            if ($filters->isCountArchived()) {
                $statutParameter[] = SignalementStatus::ARCHIVED;
            }

            $qb->andWhere('s.statut IN (:statutSelected)')
                ->setParameter('statutSelected', $statutParameter);

        // We're supposed to keep all statuses, but we remove at least the Archived
        } else {
            // If we don't want Refused status
            if (!$filters->isCountRefused()) {
                $qb->andWhere('s.statut != :statutRefused')
                    ->setParameter('statutRefused', SignalementStatus::REFUSED);
            }
            // If we don't want Archived status
            if (!$filters->isCountArchived()) {
                $qb->andWhere('s.statut != :statutArchived')
                    ->setParameter('statutArchived', SignalementStatus::ARCHIVED);
            }
            // Pour l'instant on exclue de base les brouillons et injonction bailleur
            $qb->andWhere('s.statut NOT IN (:statutDraft)')
                ->setParameter('statutDraft', [SignalementStatus::DRAFT, SignalementStatus::DRAFT_ARCHIVED, SignalementStatus::INJONCTION_BAILLEUR, SignalementStatus::INJONCTION_CLOSED]);
        }

        // Filter on creation date
        if (null !== $filters->getDateStart()) {
            $qb->andWhere('s.createdAt >= :dateStart')
                ->setParameter('dateStart', $filters->getDateStart())
                ->andWhere('s.createdAt <= :dateEnd')
                ->setParameter('dateEnd', $filters->getDateEnd());
        }

        // Filter on Signalement type (logement social)
        if ('' != $filters->getType() && 'all' != $filters->getType()) {
            switch ($filters->getType()) {
                case 'public':
                    $qb->andWhere('s.isLogementSocial = :statutLogementSocial')
                        ->setParameter('statutLogementSocial', true);
                    break;
                case 'private':
                    $qb->andWhere('s.isLogementSocial = :statutLogementSocial')
                        ->setParameter('statutLogementSocial', false);
                    break;
                case 'unset':
                    $qb->andWhere('s.isLogementSocial is NULL');
                    break;
                default:
                    break;
            }
        }

        if ($filters->getTerritory()) {
            $qb->andWhere('s.territory = :territory')
                ->setParameter('territory', $filters->getTerritory());
        }

        if ($filters->getEtiquettes()) {
            $qb->leftJoin('s.tags', 'tags');
            $qb->andWhere('tags IN (:tags)')
                ->setParameter('tags', $filters->getEtiquettes());
        }

        if ($filters->getCommunes()) {
            $communes = [];
            foreach ($filters->getCommunes() as $city) {
                $communes[] = $city;
                if (isset(CommuneHelper::COMMUNES_ARRONDISSEMENTS[$city])) {
                    $communes = array_merge($communes, CommuneHelper::COMMUNES_ARRONDISSEMENTS[$city]);
                }
            }
            $qb->andWhere('s.villeOccupant IN (:communes)')
                ->setParameter('communes', $communes);
        }

        if ($filters->getEpcis()) {
            $subQuery = $qb->getEntityManager()->createQueryBuilder()
                ->select('DISTINCT s2.id')
                ->from(Signalement::class, 's2')
                ->innerJoin(
                    Commune::class,
                    'c2',
                    'WITH',
                    's2.cpOccupant = c2.codePostal AND s2.inseeOccupant = c2.codeInsee AND c2.epci IN (:epcis)'
                );
            $qb->andWhere('s.id IN ('.$subQuery->getDQL().')')->setParameter('epcis', $filters->getEpcis());
        }

        if ($filters->getPartners() && $filters->getPartners()->count() > 0) {
            $qb->leftJoin('s.affectations', 'affectations')
                ->leftJoin('affectations.partner', 'partner')
                ->andWhere('partner IN (:partners)')
                ->setParameter('partners', $filters->getPartners());
        }

        return $qb;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function countAffectationsByPartenaireFiltered(StatisticsFilters $statisticsFilters): array
    {
        $qb = $this->entityManager->createQueryBuilder()->from(Affectation::class, 'a');
        $qb->select('a.id, a.statut, partner.id, partner.nom')
            ->leftJoin('a.signalement', 's');
        if (!$statisticsFilters->getPartners() || $statisticsFilters->getPartners()->isEmpty()) {
            $qb->leftJoin('a.partner', 'partner');
        }

        $qb = $this->addFiltersToQueryBuilder($qb, $statisticsFilters);

        return $qb->getQuery()
            ->getResult();
    }
}
