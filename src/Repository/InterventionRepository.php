<?php

namespace App\Repository;

use App\Dto\Request\CalendarEventRequest;
use App\Entity\Affectation;
use App\Entity\Enum\InterventionType;
use App\Entity\Enum\SignalementStatus;
use App\Entity\Intervention;
use App\Entity\Signalement;
use App\Entity\User;
use App\Entity\UserSignalementSubscription;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Clock\ClockInterface;

/**
 * @extends ServiceEntityRepository<Intervention>
 *
 * @method Intervention|null find($id, $lockMode = null, $lockVersion = null)
 * @method Intervention|null findOneBy(array<string, mixed> $criteria, array<string, mixed>|null $orderBy = null)
 * @method Intervention[]    findAll()
 * @method Intervention[]    findBy(array<string, mixed> $criteria, array<string, mixed>|null $orderBy = null, $limit = null, $offset = null)
 */
class InterventionRepository extends ServiceEntityRepository
{
    private const int NB_DAYS_DELAY_NOTIFICATION_VISIT_PAST = 2;
    private const int NB_DAYS_DELAY_NOTIFICATION_VISIT_FUTURE = -2;

    public function __construct(ManagerRegistry $registry,
        private readonly ClockInterface $clock, )
    {
        parent::__construct($registry, Intervention::class);
    }

    /**
     * @return array<int, Intervention>
     */
    public function getVisitsToNotify(int $delay): array
    {
        $queryBuilder = $this->createQueryBuilder('i');

        return $queryBuilder
            ->where('i.status = :planned')
            ->setParameter('planned', Intervention::STATUS_PLANNED)
            ->andWhere('i.type = :visite')
            ->setParameter('visite', InterventionType::VISITE->name)
            ->andWhere('DATEDIFF(CURRENT_DATE(),i.scheduledAt) = :day_delay')
            ->setParameter('day_delay', $delay)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return array<int, Intervention>
     */
    public function getFutureVisits(): array
    {
        return $this->getVisitsToNotify(self::NB_DAYS_DELAY_NOTIFICATION_VISIT_FUTURE);
    }

    /**
     * @return array<int, Intervention>
     */
    public function getPastVisits(): array
    {
        return $this->getVisitsToNotify(self::NB_DAYS_DELAY_NOTIFICATION_VISIT_PAST);
    }

    /**
     * @return array<int, Intervention>
     */
    public function getOrderedVisitesForSignalement(Signalement $signalement): array
    {
        $queryBuilder = $this->createQueryBuilder('i');

        return $queryBuilder
            ->andWhere('i.signalement = :signalement')
            ->setParameter('signalement', $signalement)
            ->orderBy('i.scheduledAt', \SortDirection::Descending)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return array<int, Intervention>
     */
    public function getPendingVisitesForSignalement(Signalement $signalement): array
    {
        $queryBuilder = $this->createQueryBuilder('i');

        return $queryBuilder
            ->where('i.status = :planned')
            ->setParameter('planned', Intervention::STATUS_PLANNED)
            ->andWhere('i.type = :visite')
            ->setParameter('visite', InterventionType::VISITE->name)
            ->andWhere('i.signalement = :signalement')
            ->setParameter('signalement', $signalement)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return array<int, Intervention>
     */
    public function getVisitesDoneForSignalement(Signalement $signalement): array
    {
        $queryBuilder = $this->createQueryBuilder('i');

        return $queryBuilder
            ->andWhere('i.signalement = :signalement')
            ->setParameter('signalement', $signalement)
            ->andWhere('i.status = :done')
            ->setParameter('done', Intervention::STATUS_DONE)
            ->getQuery()
            ->getResult();
    }

    public function findNextForUser(User $user, ?int $territoryId = null): ?Intervention
    {
        return $this->createVisitesForUserQueryBuilder($user, $territoryId)
            ->andWhere('i.scheduledAt >= :now')
            ->setParameter('now', $this->clock->now()->setTime(0, 0, 0))
            ->orderBy('i.scheduledAt', \SortDirection::Ascending)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @return array<int, Intervention>
     */
    public function findAllFilteredForUser(
        User $user,
        ?CalendarEventRequest $calendarEventRequest = null,
    ): array {
        $territoire = $calendarEventRequest?->getTerritoire();
        $territoryId = empty($territoire) ? null : (int) $territoire;

        $qb = $this->createVisitesForUserQueryBuilder($user, $territoryId)
            // Fin exclue : dateFin est le lendemain du dernier jour affiché
            ->andWhere('i.scheduledAt >= :start AND i.scheduledAt < :end')
            ->setParameter('start', new \DateTimeImmutable($calendarEventRequest?->getDateDebut() ?? 'today'))
            ->setParameter('end', new \DateTimeImmutable($calendarEventRequest?->getDateFin() ?? 'tomorrow'));

        // TODO : voir avec Mathilde le comportement pour les agents sur le dashboard
        // Filtre par "mes dossiers"
        if ('1' === $calendarEventRequest?->getUniquementMesDossiers()) {
            $existsSubscription = $this->getEntityManager()->createQueryBuilder()
                ->select('1')
                ->from(UserSignalementSubscription::class, 'uss')
                ->where('uss.signalement = i.signalement')
                ->andWhere('uss.user = :currentUser')
                ->getDQL();
            $qb->andWhere($qb->expr()->exists($existsSubscription))
                ->setParameter('currentUser', $user);
        }

        if ($user->isTerritoryAdmin()) {
            // Filtre opérateurs : identifiants de partenaires et/ou "extern" (visites sans partenaire)
            $operateurs = $calendarEventRequest?->getOperateurs() ?? [];
            if (!empty($operateurs)) {
                $partnerIds = array_values(array_filter($operateurs, static fn (string $id): bool => 'extern' !== $id));
                $operatorConditions = [];
                if (!empty($partnerIds)) {
                    $operatorConditions[] = 'i.partner IN (:operatorPartnerIds)';
                    $qb->setParameter('operatorPartnerIds', $partnerIds);
                }
                if (\in_array('extern', $operateurs, true)) {
                    $operatorConditions[] = 'i.partner IS NULL';
                }
                $qb->andWhere(implode(' OR ', $operatorConditions));
            }
        }

        $qb->orderBy('i.scheduledAt', \SortDirection::Ascending);

        return $qb->getQuery()->getResult();
    }

    /**
     * Visites affichables (calendrier, tableau de bord) que l'utilisateur peut voir :
     * - SA : toutes, ou celles du territoire demandé
     * - RT : celles de son territoire
     * - autres utilisateurs : celles de leurs partenaires toujours affectés, ou du territoire demandé
     */
    private function createVisitesForUserQueryBuilder(User $user, ?int $territoryId = null): QueryBuilder
    {
        $qb = $this->createQueryBuilder('i')
            ->innerJoin('i.signalement', 's')
            ->innerJoin('s.address', 'address')
            ->leftJoin('i.partner', 'p')
            ->addSelect('s', 'p')
            ->andWhere('i.type IN (:visiteTypes)')
            ->setParameter('visiteTypes', [InterventionType::VISITE->name, InterventionType::VISITE_CONTROLE->name]) // TODO enlever avec suppression ARRETE
            ->andWhere('i.status != :canceled')
            ->setParameter('canceled', Intervention::STATUS_CANCELED)
            ->andWhere('i.partner IS NOT NULL OR i.externalOperator IS NOT NULL')
            ->andWhere('s.statut IN (:includedStatus)')
            ->setParameter('includedStatus', [SignalementStatus::ACTIVE]); // TODO : voir avec Mathilde si on élargit ?

        if ($user->isTerritoryAdmin()) {
            // RT : toutes les visites de son territoire
            $qb->andWhere('address.territory = :userTerritory')
                ->setParameter('userTerritory', $user->getFirstTerritory());
        } elseif (!$user->isSuperAdmin()) {
            // Agents et admins partenaire : les visites de leurs partenaires toujours affectés
            $existsAffectation = $this->getEntityManager()->createQueryBuilder()
                ->select('1')
                ->from(Affectation::class, 'a')
                ->where('a.signalement = i.signalement')
                ->andWhere('a.partner = i.partner')
                ->getDQL();
            $qb->andWhere('i.partner IN (:userPartners)')
                ->andWhere($qb->expr()->exists($existsAffectation))
                ->setParameter('userPartners', $user->getPartners());
        }

        // Filtre territoire (SA et utilisateurs multi-territoires)
        if (!$user->isTerritoryAdmin() && null !== $territoryId) {
            $qb->andWhere('address.territory = :territory')
                ->setParameter('territory', $territoryId);
        }

        return $qb;
    }
}
