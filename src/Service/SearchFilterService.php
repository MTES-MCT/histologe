<?php

namespace App\Service;

use App\Entity\Affectation;
use App\Entity\Enum\Qualification;
use App\Entity\Enum\QualificationStatus;
use App\Entity\Enum\SignalementStatus;
use App\Entity\Signalement;
use App\Entity\Territory;
use App\Entity\User;
use App\Repository\NotificationRepository;
use App\Repository\SignalementQualificationRepository;
use App\Repository\SuiviRepository;
use App\Repository\TerritoryRepository;
use DateInterval;
use DateTime;
use Doctrine\DBAL\Exception;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;

class SearchFilterService
{
    private array $filters;
    private Request $request;
    private int $countActive;
    private const REQUESTS = [
        'searchterms',
        'territories',
        'statuses',
        'cities',
        'partners',
        'closed_affectation',
        'criteres',
        'allocs',
        'housetypes',
        'declarants',
        'proprios',
        'interventions',
        'avant1949',
        'enfantsM6',
        'affectations',
        'visites',
        'delays',
        'scores',
        'dates',
        'nde',
        'tags',
    ];

    public function __construct(
        private Security $security,
        private NotificationRepository $notificationRepository,
        private SuiviRepository $suiviRepository,
        private TerritoryRepository $territoryRepository,
        private EntityManagerInterface $entityManager,
        private SignalementQualificationRepository $signalementQualificationRepository
    ) {
    }

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
        $this->countActive = 0;
        $request = $this->getRequest();
        /** @var User $user */
        $user = $this->security->getUser();
        $filters = self::REQUESTS;
        $this->filters = [];
        $territory = $this->getTerritory($user, $request);
        foreach ($filters as $filter) {
            $this->filters[$filter] = $request->get('bo-filters-'.$filter) ?? null;

            if ($request->get('bo-filters-'.$filter)) {
                switch ($filter) {
                    case 'dates':
                        $filterDates = $request->get('bo-filters-'.$filter);
                        if (!empty($filterDates['on']) || !empty($filterDates['off'])) {
                            ++$this->countActive;
                        }
                        break;
                    case 'scores':
                        $filterScores = $request->get('bo-filters-'.$filter);
                        if ('0' != $filterScores['on'] || '100' != $filterScores['off']) {
                            ++$this->countActive;
                        }
                        break;
                    default:
                        ++$this->countActive;
                        break;
                }
            }
        }

        $this->filters['page'] = $request->get('page') ?? 1;

        if ($request->isMethod('GET')) {
            if ($request->query->get('statut')) {
                ++$this->countActive;
                $this->filters['statuses'] = [$request->query->get('statut')];
            }

            if ($request->query->get('partenaires')) {
                ++$this->countActive;
                $this->filters['partners'] = [$request->query->get('partenaires')];
            }

            if ($request->query->get('nouveau_suivi')) {
                ++$this->countActive;
                $signalementIds = $this->notificationRepository->findSignalementNewSuivi($user, $territory);
                $this->filters['signalement_ids'] = $signalementIds;
            }

            if ($this->security->isGranted('ROLE_ADMIN') && $request->query->get('territoire_id')) {
                ++$this->countActive;
                $this->filters['territories'] = [$request->query->get('territoire_id')];
            }

            if ($request->query->get('closed_affectation')) {
                ++$this->countActive;
                $this->filters['closed_affectation'] = [$request->query->get('closed_affectation')];
            }

            if ($request->query->get('nde')) {
                ++$this->countActive;
                $this->filters['nde'] = [QualificationStatus::NDE_AVEREE->name, QualificationStatus::NDE_CHECK->name];
            }

            if ($request->query->get('sort')) {
                $this->filters['sort'] = $request->query->get('sort');
            }
        }

        if (!empty($this->filters['delays'])
            || $request->isMethod('GET') && $request->query->get('sans_suivi_periode')
        ) {
            if ($request->isMethod('GET') && $request->query->get('sans_suivi_periode')) {
                ++$this->countActive;
            }
            $period = $this->filters['delays'] ?? $request->query->get('sans_suivi_periode');
            $partner = \in_array(User::ROLE_USER_PARTNER, $user->getRoles()) ? $user->getPartner() : null;
            $this->filters['delays'] = (int) $period;
            $this->filters['delays_territory'] = $territory;
            $this->filters['delays_partner'] = $partner;
        }

        if ($request->query->get('sort')) {
            $this->filters['sort'] = $request->query->get('sort');
        }

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

    public function getCountActive(): int
    {
        return $this->countActive;
    }

    /**
     * @throws Exception
     */
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
                $qb->andWhere('a.partner IS NULL');
            } else {
                $qb->andWhere('a.partner IN (:partners)');
                if (!empty($filters['affectations'])) {
                    $qb->andWhere('a.statut IN (:affectations)')
                    ->setParameter('affectations', $filters['affectations']);
                }
                $qb->setParameter('partners', $filters['partners']);
            }
        }
        if (!empty($filters['closed_affectation'])) {
            $qb->having('affectationPartnerName IS NOT NULL');
            if (\in_array('ALL_OPEN', $filters['closed_affectation'])) {
                // les id de tous les signalements ayant au moins une affectation fermée :
                $subquery = $this->entityManager->getRepository(Affectation::class)->createQueryBuilder('a')
                    ->select('DISTINCT s.id')
                    ->innerJoin('a.signalement', 's')
                    ->where('a.statut = '.Affectation::STATUS_CLOSED);

                // les signalements n'ayant aucune affectation fermée :
                $qb->andWhere('s.id NOT IN (:subquery)')
                    ->setParameter('subquery', $subquery->getQuery()->getSingleColumnResult());
            }
            if (\in_array('ONE_CLOSED', $filters['closed_affectation'])) {
                // les id de tous les signalements ayant au moins une affectation fermée :
                $subqueryClosedAffectation = $this->entityManager->getRepository(Affectation::class)->createQueryBuilder('a')
                    ->select('DISTINCT IDENTITY(a.signalement)')
                    ->innerJoin('a.signalement', 's')
                    ->where('a.statut = '.Affectation::STATUS_CLOSED)
                    ->andWhere('s.statut != :status_archived')
                    ->setParameter('status_archived', Signalement::STATUS_ARCHIVED);

                if (!empty($filters['territories'])) {
                    $subqueryClosedAffectation->andWhere('a.territory IN (:territories)')
                        ->setParameter('territories', $filters['territories']);
                }

                // les id de tous les signalements ayant au moins une affectation non fermée :
                $subqueryUnclosedAffectation = $this->entityManager->getRepository(Affectation::class)->createQueryBuilder('a')
                    ->select('DISTINCT IDENTITY(a.signalement)')
                    ->innerJoin('a.signalement', 's')
                    ->where('a.statut != '.Affectation::STATUS_CLOSED)
                    ->andWhere('s.statut != :status_archived')
                    ->setParameter('status_archived', Signalement::STATUS_ARCHIVED);

                if (!empty($filters['territories'])) {
                    $subqueryUnclosedAffectation->andWhere('a.territory IN (:territories)')
                        ->setParameter('territories', $filters['territories']);
                }

                // les signalements ayant au moins une affectation fermée :
                $qb->andWhere('s.id IN (:subqueryClosedAffectation)')
                    ->andWhere('s.id IN (:subqueryUnclosedAffectation)')
                    ->setParameter('subqueryClosedAffectation', $subqueryClosedAffectation->getQuery()->getSingleColumnResult())
                    ->setParameter('subqueryUnclosedAffectation', $subqueryUnclosedAffectation->getQuery()->getSingleColumnResult());
            }
            if (\in_array('ALL_CLOSED', $filters['closed_affectation'])) {
                // les id de tous les signalements ayant au moins une affectation non fermée :
                $subquery = $this->entityManager->getRepository(Affectation::class)->createQueryBuilder('a')
                    ->select('DISTINCT s.id')
                    ->leftJoin('a.signalement', 's')
                    ->where('a.statut != '.Affectation::STATUS_CLOSED);

                // les signalements n'ayant aucune affectation non fermée ou qui sont fermés
                $qb->andWhere(
                    $qb->expr()->orX(
                        $qb->expr()->notIn('s.id', ':idUnclosedAffectation'),
                        $qb->expr()->eq('s.statut', ':statut')
                    )
                )
                ->setParameter('idUnclosedAffectation', $subquery->getQuery()->getSingleColumnResult())
                ->setParameter('statut', Signalement::STATUS_ARCHIVED);
            }
        }
        if (!empty($filters['tags'])) {
            $qb->leftJoin('s.tags', 't');
            $qb->andWhere('t.id IN (:tag)')->setParameter('tag', $filters['tags']);
        }
        if (!empty($filters['statuses'])) {
            /** @var User $user */
            $user = $this->security->getUser();
            if ($user->isSuperAdmin() || $user->isTerritoryAdmin()) {
                $qb->andWhere('s.statut IN (:statuses)')
                    ->setParameter('statuses', $filters['statuses']);
            } else {
                // @todo: filter more than one status for partner
                $statuses = array_map(function ($status) {
                    return SignalementStatus::tryFrom($status)?->mapAffectationStatus();
                }, $filters['statuses']);
                $statuses = array_shift($statuses);
                $qb->having('affectationStatus LIKE :status_affectation')
                    ->setParameter('status_affectation', '%'.$statuses.'%');
            }
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
            $qb->leftJoin('s.criteres', 'c');
            $qb->andWhere('c.id IN (:criteres)')
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
            $signalementIds = $this->suiviRepository->findSignalementNoSuiviSince(
                $filters['delays'],
                $filters['delays_territory'],
                $filters['delays_partner']
            );
            $qb->andWhere('s.id IN (:signalement_ids)')
                ->setParameter('signalement_ids', $signalementIds);
        }
        if (!empty($filters['scores'])) {
            if (!empty($filters['scores']['on'])) {
                $qb->andWhere('s.newScoreCreation >= :score_on')
                    ->setParameter('score_on', $filters['scores']['on']);
            } elseif (!empty($filters['scores']['off'])) {
                $qb->andWhere('s.newScoreCreation <= :score_off')
                    ->setParameter('score_off', $filters['scores']['off']);
            }
        }
        if (!empty($filters['territories'])) {
            $qb->andWhere('s.territory IN (:territories)')
                ->setParameter('territories', $filters['territories']);
        }

        if (!empty($filters['nde'])) {
            $subqueryResults = $this->signalementQualificationRepository->findSignalementsByQualification(Qualification::NON_DECENCE_ENERGETIQUE, $filters['nde']);
            $qb->andWhere('s.id IN (:subqueryResults)')
                ->setParameter('subqueryResults', $subqueryResults);
        }

        if (!empty($filters['signalement_ids'])) {
            $qb->andWhere('s.id IN (:signalement_ids)')
                ->setParameter('signalement_ids', $filters['signalement_ids']);
        }

        return $qb;
    }

    private function getTerritory(User $user, Request $request): ?Territory
    {
        $territory = null;
        if ($user->isSuperAdmin() && $request->query->get('territoire_id')) {
            $territory = $this->territoryRepository->find($request->query->get('territoire_id'));
        } elseif (!$user->isSuperAdmin()) {
            $territory = $user->getTerritory();
        }

        return $territory;
    }
}
