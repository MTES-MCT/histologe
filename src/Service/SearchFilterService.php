<?php

namespace App\Service;

use DateInterval;
use DateTime;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\HttpFoundation\Request;

class SearchFilterService
{
    private array $filters;
    private Request $request;

    public function setRequest(Request $request): static
    {
        $this->request = $request;

        return $this;
    }

    public function getFilters(): ?array
    {
        return $this->filters ?? null;
    }

    public function setFilters(): self
    {
        $request = $this->getRequest();
        $this->filters = [
            'searchterms' => $request->get('bo-filters-searchterms') ?? null,
            'territories' => $request->get('bo-filters-territories') ?? null,
            'statuses' => $request->get('bo-filters-statuses') ?? null,
            'cities' => $request->get('bo-filters-cities') ?? null,
            'partners' => $request->get('bo-filters-partners') ?? null,
            'criteres' => $request->get('bo-filters-criteres') ?? null,
            'allocs' => $request->get('bo-filters-allocs') ?? null,
            'housetypes' => $request->get('bo-filters-housetypes') ?? null,
            'declarants' => $request->get('bo-filters-declarants') ?? null,
            'proprios' => $request->get('bo-filters-proprios') ?? null,
            'interventions' => $request->get('bo-filters-interventions') ?? null,
            'avant1949' => $request->get('bo-filters-avant1949') ?? null,
            'enfantsM6' => $request->get('bo-filters-enfantsM6') ?? null,
            'handicaps' => $request->get('bo-filters-handicaps') ?? null,
            'affectations' => $request->get('bo-filters-affectations') ?? null,
            'visites' => $request->get('bo-filters-visites') ?? null,
            'delays' => $request->get('bo-filters-delays') ?? null,
            'scores' => $request->get('bo-filters-scores') ?? null,
            'dates' => $request->get('bo-filters-dates') ?? null,
            'tags' => $request->get('bo-filters-tags') ?? null,
            'page' => $request->get('page') ?? 1,
        ];

        return $this;
    }

    private function getRequest(): Request
    {
        return $this->request;
    }

    public function getFilter(string $filterName): ?string
    {
        return $this->filters[$filterName] ?? null;
    }

    public function setFilter(string $filterName, string $filterValue): void
    {
        $this->filters[$filterName] = $filterValue;
    }

    public function removeFilter(string $filterName): void
    {
        unset($this->filters[$filterName]);
    }

    public function getFiltersAsString(): string
    {
        $filters = [];
        foreach ($this->filters as $filterName => $filterValue) {
            $filters[] = $filterName.'='.$filterValue;
        }

        return implode('&', $filters);
    }

    public function getFiltersAsArray(): array
    {
        return $this->filters;
    }

    public function applyFilters(QueryBuilder $qb, array $filters): QueryBuilder
    {
        if (!empty($filters['searchterms'])) {
            if (preg_match('/([0-9]{4})-[0-9]{0,6}/', $filters['searchterms'])) {
                $qb->andWhere('s.reference = :searchterms');
                $qb->setParameter('searchterms', $filters['searchterms']);
            } elseif (preg_match('/([0-9]{5})/', $filters['searchterms'])) {
                $qb->andWhere('s.cpOccupant = :searchterms');
                $qb->setParameter('searchterms', $filters['searchterms']);
            } else {
                $qb->andWhere('LOWER(s.nomOccupant) LIKE :searchterms
                OR LOWER(s.prenomOccupant) LIKE :searchterms
                OR LOWER(s.reference) LIKE :searchterms
                OR LOWER(s.adresseOccupant) LIKE :searchterms
                OR LOWER(s.villeOccupant) LIKE :searchterms
                OR LOWER(s.nomProprio) LIKE :searchterms');
                $qb->setParameter('searchterms', '%'.strtolower($filters['searchterms']).'%');
            }
        }
        if (!empty($filters['affectations']) && (bool) empty($filters['partners'])) {
            $qb->andWhere('a.statut IN (:affectations)')
                ->setParameter('affectations', $filters['affectations']);
        }
        if (!empty($filters['partners'])) {
            if (\in_array('AUCUN', $filters['partners'])) {
                $qb->andWhere('affectations IS NULL');
            } else {
                $qb->andWhere('partner IN (:partners)');
                if (!empty($filters['affectations'])) {
                    $qb->andWhere('a.statut IN (:affectations)')->setParameter('affectations', $filters['affectations']);
                }
                $qb->setParameter('partners', $filters['partners']);
            }
        }
        if (!empty($filters['tags'])) {
            foreach ($filters['tags'] as $tag) {
                $qb->andWhere(':tag IN (tags)')->setParameter('tag', $tag);
            }
        }
        if (!empty($filters['statuses'])) {
            $qb->andWhere('s.statut IN (:statuses)')
                ->setParameter('statuses', $filters['statuses']);
        }
        if (!empty($filters['cities'])) {
            $qb->andWhere('s.villeOccupant IN (:cities)')
                ->setParameter('cities', $filters['cities']);
        }
        if (!empty($filters['visites'])) {
            $qb->andWhere('IF(s.dateVisite IS NOT NULL,1,0) IN (:visites)')
                ->setParameter('visites', $filters['visites']);
        }
        if (!empty($filters['enfantsM6'])) {
            $qb->andWhere('IF(s.nbEnfantsM6 IS NOT NULL AND s.nbEnfantsM6 != 0,1,0) IN (:enfantsM6)')
                ->setParameter('enfantsM6', $filters['enfantsM6']);
        }
        if (!empty($filters['avant1949'])) {
            $qb->andWhere('s.isConstructionAvant1949 IN (:avant1949)')
                ->setParameter('avant1949', $filters['avant1949']);
        }
        if (!empty($filters['handicaps'])) {
            $qb->andWhere('s.isSituationHandicap IN (:handicaps)')
                ->setParameter('handicaps', $filters['handicaps']);
        }
        if (!empty($filters['dates'])) {
            $field = 's.createdAt';
            if (!empty($filters['visites'])) {
                $field = 's.dateVisite';
            }
            if (!empty($filters['dates']['on'])) {
                $qb->andWhere($field.' >= :date_in')
                    ->setParameter('date_in', $filters['dates']['on']);
            }
            if (!empty($filters['dates']['off'])) {
                $date_off_p1d = new DateTime($filters['dates']['off']);
                $date_off_p1d->add(new DateInterval('P1D'));
                $qb->andWhere($field.' <= :date_off')
                    ->setParameter('date_off', $date_off_p1d->format('Y-m-d'));
            }
        }

        if (!empty($filters['criteres'])) {
            $qb->andWhere('criteres IN (:criteres)')
                ->setParameter('criteres', $filters['criteres']);
        }
        if (!empty($filters['housetypes'])) {
            $qb->andWhere('s.isLogementSocial IN (:housetypes)')
                ->setParameter('housetypes', $filters['housetypes']);
        }
        if (!empty($filters['allocs'])) {
            $qb->andWhere('s.isAllocataire IN (:allocs)')
                ->setParameter('allocs', $filters['allocs']);
        }
        if (!empty($filters['declarants'])) {
            $qb->andWhere('s.isNotOccupant IN (:declarants)')
                ->setParameter('declarants', $filters['declarants']);
        }
        if (!empty($filters['proprios'])) {
            $qb->andWhere('s.isProprioAverti IN (:proprios)')
                ->setParameter('proprios', $filters['proprios']);
        }
        if (!empty($filters['interventions'])) {
            $qb->andWhere('s.isRefusIntervention IN (:interventions)')
                ->setParameter('interventions', $filters['interventions']);
        }
        if (!empty($filters['delays'])) {
//            dd(max($filters['delays']));
            $qb->andWhere('DATEDIFF(NOW(),suivis.createdAt) >= :delays')
                ->setParameter('delays', $filters['delays']);
        }
        if (!empty($filters['scores'])) {
//            dd(max($filters['delays']));
            if (!empty($filters['scores']['on'])) {
                $qb->andWhere('s.scoreCreation >= :score_on')
                    ->setParameter('score_on', $filters['scores']['on']);
            } elseif (!empty($filters['scores']['off'])) {
                $qb->andWhere('s.scoreCreation <= :score_off')
                    ->setParameter('score_off', $filters['scores']['off']);
            }
        }
        if (!empty($filters['territories'])) {
            $qb->andWhere('s.territory IN (:territories)')
                ->setParameter('territories', $filters['territories']);
        }

        return $qb;
    }
}
