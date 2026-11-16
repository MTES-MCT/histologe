<?php

namespace App\Repository;

use App\Dto\Request\CalendarEventRequest;
use App\Entity\ClubEvent;
use App\Entity\User;
use App\Service\ListFilters\SearchClubEvent;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Clock\ClockInterface;

/**
 * @extends ServiceEntityRepository<ClubEvent>
 */
class ClubEventRepository extends ServiceEntityRepository
{
    public function __construct(
        ManagerRegistry $registry,
        private readonly ClockInterface $clock,
    ) {
        parent::__construct($registry, ClubEvent::class);
    }

    /**
     * @return array<ClubEvent>
     */
    public function findInExactlyNbDays(int $nbDays): array
    {
        $targetDate = $this->clock->now()->modify("+$nbDays days");

        $qb = $this->createQueryBuilder('c')
            ->andWhere('c.dateEvent BETWEEN :startOfDay AND :endOfDay')
            ->setParameter('startOfDay', $targetDate->setTime(0, 0, 0))
            ->setParameter('endOfDay', $targetDate->setTime(23, 59, 59))
            ->orderBy('c.dateEvent', \SortDirection::Ascending);

        return $qb->getQuery()->getResult();
    }

    public function findNextForUser(User $user): ?ClubEvent
    {
        $qb = $this->createQueryBuilder('c')
            ->andWhere('c.dateEvent >= :now')
            ->setParameter('now', $this->clock->now()->setTime(0, 0, 0))
            ->orderBy('c.dateEvent', \SortDirection::Ascending)
            ->setMaxResults(1);

        $this->restrictToUser($qb, $user);

        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * @return array<ClubEvent>
     */
    public function findAllFilteredForUser(
        User $user,
        ?CalendarEventRequest $calendarEventRequest = null,
    ): array {
        $qb = $this->createQueryBuilder('c')
            // Fin exclue : dateFin est le lendemain du dernier jour affiché
            ->andWhere('c.dateEvent >= :start AND c.dateEvent < :end')
            ->setParameter('start', new \DateTimeImmutable($calendarEventRequest?->getDateDebut() ?? 'today'))
            ->setParameter('end', new \DateTimeImmutable($calendarEventRequest?->getDateFin() ?? 'tomorrow'))
            ->orderBy('c.dateEvent', \SortDirection::Ascending);

        $this->restrictToUser($qb, $user);

        return $qb->getQuery()->getResult();
    }

    /**
     * Limite aux événements auxquels l'utilisateur est invité :
     * - SA : tous les événements
     * - RT : événements correspondant à son rôle
     * - autres utilisateurs : événements correspondant à son rôle, au type et à la compétence de l'un de ses partenaires
     *
     * Attention : une modification sur cette méthode doit être répercutée dans la méthode findUsersToNotifyForClubEvent du UserRepository
     */
    private function restrictToUser(QueryBuilder $qb, User $user): void
    {
        if ($user->isSuperAdmin()) {
            return;
        }

        $userRoles = array_keys(array_filter([
            User::ROLE_ADMIN_TERRITORY => $user->isTerritoryAdmin(),
            User::ROLE_ADMIN_PARTNER => $user->isPartnerAdmin(),
            User::ROLE_USER_PARTNER => $user->isUserPartner(),
        ]));
        $this->addJsonMatchCondition($qb, 'c.userRoles', 'userRole', $userRoles);

        if ($user->isTerritoryAdmin()) {
            return;
        }

        $partnerTypes = [];
        $partnerCompetences = [];
        foreach ($user->getPartners() as $partner) {
            if (null !== $partner->getType()) {
                $partnerTypes[] = $partner->getType()->value;
            }
            foreach ($partner->getCompetence() ?? [] as $competence) {
                $partnerCompetences[] = $competence->value;
            }
        }
        $this->addJsonMatchCondition($qb, 'c.partnerTypes', 'partnerType', $partnerTypes);
        $this->addJsonMatchCondition($qb, 'c.partnerCompetences', 'partnerCompetence', $partnerCompetences);
    }

    /**
     * Condition sur une colonne JSON de restrictions : aucune restriction, ou au moins une valeur en commun.
     *
     * @param array<string> $values
     */
    private function addJsonMatchCondition(QueryBuilder $qb, string $field, string $paramPrefix, array $values): void
    {
        $conditions = ['JSON_LENGTH('.$field.') = 0'];
        foreach (array_values(array_unique($values)) as $index => $value) {
            $paramName = $paramPrefix.'_'.$index;
            $conditions[] = 'JSON_CONTAINS('.$field.', :'.$paramName.') = 1';
            $qb->setParameter($paramName, json_encode($value));
        }

        $qb->andWhere(implode(' OR ', $conditions));
    }

    /**
     * @return Paginator<ClubEvent>
     */
    public function findFilteredPaginated(SearchClubEvent $searchClubEvent, int $maxResult): Paginator
    {
        $qb = $this->createQueryBuilder('c');

        if (!empty($searchClubEvent->getOrderType())) {
            [$orderField, $orderDirection] = explode('-', $searchClubEvent->getOrderType());
            $qb->orderBy($orderField, 'DESC' === $orderDirection ? \SortDirection::Descending : \SortDirection::Ascending);
        } else {
            $qb->orderBy('c.dateEvent', \SortDirection::Ascending);
        }

        if (true === $searchClubEvent->getIsInFuture()) {
            $qb->andWhere('c.dateEvent >= :now')
               ->setParameter('now', (new \DateTimeImmutable('today'))->setTime(0, 0, 0));
        } elseif (false === $searchClubEvent->getIsInFuture()) {
            $qb->andWhere('c.dateEvent < :now')
               ->setParameter('now', (new \DateTimeImmutable('today'))->setTime(0, 0, 0));
        }

        if ($searchClubEvent->getQueryName()) {
            $qb->andWhere('LOWER(c.name) LIKE :queryName');
            $qb->setParameter('queryName', '%'.strtolower($searchClubEvent->getQueryName()).'%');
        }

        if ($searchClubEvent->getPartnerType()) {
            $qb->andWhere('JSON_CONTAINS(c.partnerTypes, :partnerType) = 1');
            $qb->setParameter('partnerType', json_encode($searchClubEvent->getPartnerType()));
        }

        if ($searchClubEvent->getPartnerCompetence()) {
            $qb->andWhere('JSON_CONTAINS(c.partnerCompetences, :partnerCompetence) = 1');
            $qb->setParameter('partnerCompetence', json_encode($searchClubEvent->getPartnerCompetence()));
        }

        $firstResult = ($searchClubEvent->getPage() - 1) * $maxResult;
        $qb->setFirstResult($firstResult)->setMaxResults($maxResult);

        return new Paginator($qb->getQuery());
    }
}
