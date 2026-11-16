<?php

namespace App\Repository;

use App\Dto\Request\CalendarEventRequest;
use App\Entity\Enum\InterventionType;
use App\Entity\Enum\SignalementStatus;
use App\Entity\Intervention;
use App\Entity\Signalement;
use App\Entity\User;
use App\Entity\UserSignalementSubscription;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

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

    public function __construct(ManagerRegistry $registry)
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
            ->andWhere('i.type IN (:visiteTypes)')
            ->setParameter('visiteTypes', [InterventionType::VISITE->name, InterventionType::VISITE_CONTROLE->name])
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
            ->andWhere('i.type IN (:visiteTypes)')
            ->setParameter('visiteTypes', [InterventionType::VISITE->name, InterventionType::VISITE_CONTROLE->name])
            ->andWhere('i.signalement = :signalement')
            ->setParameter('signalement', $signalement)
            ->andWhere('i.status = :done')
            ->setParameter('done', Intervention::STATUS_DONE)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return array<int, Intervention>
     */
    public function findAllFilteredForUser(
        User $user,
        ?CalendarEventRequest $calendarEventRequest = null,
    ): array {
        $qb = $this->createQueryBuilder('i')
            ->innerJoin('i.signalement', 's')
            ->leftJoin('i.partner', 'p') // null pour un opérateur externe
            ->addSelect('s', 'p')
            ->andWhere('i.type IN (:visiteTypes)')
            ->setParameter('visiteTypes', [InterventionType::VISITE->name, InterventionType::VISITE_CONTROLE->name])
            ->andWhere('i.status IS NULL OR i.status != :canceled')
            ->setParameter('canceled', Intervention::STATUS_CANCELED)
            ->andWhere('s.statut NOT IN (:excludedStatus)')
            ->setParameter('excludedStatus', SignalementStatus::excludedStatuses())
            // Fin exclue : dateFin est le lendemain du dernier jour affiché
            ->andWhere('i.scheduledAt >= :start AND i.scheduledAt < :end')
            ->setParameter('start', new \DateTimeImmutable($calendarEventRequest?->getDateDebut() ?? 'today'))
            ->setParameter('end', new \DateTimeImmutable($calendarEventRequest?->getDateFin() ?? 'tomorrow'));

        // filtre par "mes dossiers" : si activé, on ne garde que les visites pour lesquelles l'utilisateur est abonné au signalement
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
            // RT : toutes les visites de son territoire
            $qb->andWhere('s.territory = :userTerritory')
                ->setParameter('userTerritory', $user->getFirstTerritory());

            // Filtre opérateurs : identifiants de partenaires et/ou "extern" (visites sans partenaire)
            $operateurs = $calendarEventRequest?->getOperateurs() ?? [];
            if (!empty($operateurs)) {
                $partnerIds = array_values(array_filter($operateurs, static fn ($id): bool => 'extern' !== $id));
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
        } elseif (!$user->isSuperAdmin()) {
            // Agents et admins partenaire : les visites de leurs partenaires
            $qb->andWhere('i.partner IN (:userPartners)')
                ->setParameter('userPartners', $user->getPartners());
        }

        // Filtre territoire (SA et utilisateurs multi-territoires) : restreint encore les visites autorisées ci-dessus
        if (!$user->isTerritoryAdmin() && !empty($calendarEventRequest?->getTerritoire())) {
            $qb->andWhere('s.territory = :territory')
                ->setParameter('territory', $calendarEventRequest->getTerritoire());
        }

        $qb->orderBy('i.scheduledAt', 'ASC');

        return $qb->getQuery()->getResult();
    }
}
