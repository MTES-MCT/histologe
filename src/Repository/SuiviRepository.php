<?php

namespace App\Repository;

use App\Entity\Enum\AffectationStatus;
use App\Entity\Enum\SignalementStatus;
use App\Entity\Signalement;
use App\Entity\Suivi;
use App\Entity\Territory;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\DBAL\Exception;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * @method Suivi|null find($id, $lockMode = null, $lockVersion = null)
 * @method Suivi|null findOneBy(array $criteria, array $orderBy = null)
 * @method Suivi[]    findAll()
 * @method Suivi[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SuiviRepository extends ServiceEntityRepository
{
    public function __construct(
        ManagerRegistry $registry,
        #[Autowire(env: 'LIMIT_DAILY_RELANCES_BY_REQUEST')]
        private int $limitDailyRelancesByRequest,
    ) {
        parent::__construct($registry, Suivi::class);
    }

    /**
     * @param array<int, Territory> $territories
     *
     * @throws Exception
     */
    public function getAverageSuivi(array $territories): float
    {
        $connection = $this->getEntityManager()->getConnection();
        $whereTerritory = '';
        $parameters['statut_archived'] = SignalementStatus::ARCHIVED->value;
        $parameters['statut_draft'] = SignalementStatus::DRAFT->value;
        $parameters['statut_draft_archived'] = SignalementStatus::DRAFT_ARCHIVED->value;

        if (\count($territories)) {
            $territoriesIds = implode(',', array_keys($territories));
            $whereTerritory = 'AND s.territory_id IN (:territories)';
            $parameters['territories'] = $territoriesIds;
        }

        $sql = 'SELECT AVG(nb_suivi) as average_nb_suivi
                FROM (
                    SELECT count(*) as nb_suivi
                    FROM suivi su
                    INNER JOIN signalement s on s.id = su.signalement_id
                    WHERE s.statut NOT IN (:statut_archived, :statut_draft, :statut_draft_archived)
                    '.$whereTerritory.'
                    GROUP BY su.signalement_id
                ) as countQuery';
        $statement = $connection->prepare($sql);

        return (float) $statement->executeQuery($parameters)->fetchOne();
    }

    /**
     * @param array<int, Territory> $territories
     * @param array<int, int>       $partnersIds
     *
     * @throws Exception
     */
    public function countSignalementNoSuiviSince(
        array $territories,
        array $partnersIds = [],
    ): int {
        $connection = $this->getEntityManager()->getConnection();
        $parameters = [
            'day_period' => Suivi::DEFAULT_PERIOD_INACTIVITY,
            'type_suivi_usager' => Suivi::TYPE_USAGER,
            'type_suivi_partner' => Suivi::TYPE_PARTNER,
            'type_suivi_auto' => Suivi::TYPE_AUTO,
            'status_archived' => SignalementStatus::ARCHIVED->value,
            'status_closed' => SignalementStatus::CLOSED->value,
            'status_refused' => SignalementStatus::REFUSED->value,
            'status_draft' => SignalementStatus::DRAFT->value,
            'status_draft_archived' => SignalementStatus::DRAFT_ARCHIVED->value,
        ];

        if (\count($territories)) {
            $parameters['territories'] = implode(',', array_keys($territories));
        }
        if (!empty($partnersIds)) {
            $parameters['partners'] = implode(',', $partnersIds);
            $parameters['status_wait'] = AffectationStatus::WAIT->value;
            $parameters['status_accepted'] = AffectationStatus::ACCEPTED->value;
        }

        $sql = 'SELECT COUNT(*) as count_signalement
                FROM ('.
                        $this->getSignalementsQuery($territories, $partnersIds)
                .') as countSignalementSuivi';

        $statement = $connection->prepare($sql);

        return (int) $statement->executeQuery($parameters)->fetchOne();
    }

    /**
     * @param array<int, int> $partnersIds
     *
     * @return array<int, int|string>
     *
     * @throws Exception
     */
    public function findSignalementNoSuiviSince(
        int $period = Suivi::DEFAULT_PERIOD_INACTIVITY,
        ?Territory $territory = null,
        ?array $partnersIds = null,
    ): array {
        $connection = $this->getEntityManager()->getConnection();
        $parameters = [
            'day_period' => $period,
            'type_suivi_usager' => Suivi::TYPE_USAGER,
            'type_suivi_partner' => Suivi::TYPE_PARTNER,
            'type_suivi_auto' => Suivi::TYPE_AUTO,
            'status_archived' => SignalementStatus::ARCHIVED->value,
            'status_closed' => SignalementStatus::CLOSED->value,
            'status_refused' => SignalementStatus::REFUSED->value,
            'status_draft' => SignalementStatus::DRAFT->value,
            'status_draft_archived' => SignalementStatus::DRAFT_ARCHIVED->value,
        ];

        $territories = [];
        if (null !== $territory) {
            $parameters['territories'] = $territory->getId();
            $territories[] = $territory;
        }

        if (!empty($partnersIds)) {
            $parameters['partners'] = implode(',', $partnersIds);
            $parameters['status_wait'] = AffectationStatus::WAIT->value;
            $parameters['status_accepted'] = AffectationStatus::ACCEPTED->value;
        }
        $sql = $this->getSignalementsQuery($territories, $partnersIds);
        $statement = $connection->prepare($sql);

        return $statement->executeQuery($parameters)->fetchFirstColumn();
    }

    /**
     * @param array<int, Territory> $territories
     *
     * @throws NonUniqueResultException
     * @throws NoResultException
     */
    public function countSuiviPartner(array $territories): int
    {
        $qb = $this->createQueryBuilder('s');
        $qb->select('COUNT(s) as nb_suivi')
            ->innerJoin('s.signalement', 'sig')
            ->where('sig.statut NOT IN (:statutList)')
            ->andWhere('s.type = :type_suivi')
            ->setParameter('statutList', [SignalementStatus::ARCHIVED->value, SignalementStatus::DRAFT->value, SignalementStatus::DRAFT_ARCHIVED->value])
            ->setParameter('type_suivi', Suivi::TYPE_PARTNER);

        if (\count($territories)) {
            $qb->andWhere('sig.territory IN (:territories)')->setParameter('territories', $territories);
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * @param array<int, Territory> $territories
     *
     * @throws NonUniqueResultException
     * @throws NoResultException
     */
    public function countSuiviUsager(array $territories): int
    {
        $qb = $this->createQueryBuilder('s');
        $qb->select('COUNT(s) as nb_suivi')
            ->innerJoin('s.signalement', 'sig')
            ->leftJoin('s.createdBy', 'u')
            ->where('sig.statut NOT IN (:statutList)')
            ->andWhere('s.type = :type_suivi')
            ->setParameter('type_suivi', Suivi::TYPE_USAGER)
            ->setParameter('statutList', [SignalementStatus::ARCHIVED->value, SignalementStatus::DRAFT->value, SignalementStatus::DRAFT_ARCHIVED->value]);

        if (\count($territories)) {
            $qb->andWhere('sig.territory IN (:territories)')->setParameter('territories', $territories);
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * @param array<int, Territory> $territories
     * @param array<int, int>|null  $partnersIds
     */
    private function getSignalementsQuery(
        array $territories,
        ?array $partnersIds = null,
    ): string {
        $whereTerritory = $wherePartner = $innerPartnerJoin = '';

        if (\count($territories)) {
            $whereTerritory = 'AND s.territory_id IN (:territories)';
        }

        if (!empty($partnersIds)) {
            $wherePartner = 'AND a.partner_id IN (:partners)';
            $innerPartnerJoin = 'INNER JOIN affectation a ON a.signalement_id = su.signalement_id AND a.statut IN (:status_wait, :status_accepted)';
        }

        return 'SELECT su.signalement_id, MAX(su.created_at) as last_posted_at
                FROM suivi su
                INNER JOIN signalement s on s.id = su.signalement_id
                '.$innerPartnerJoin.'
                WHERE type in (:type_suivi_usager,:type_suivi_partner, :type_suivi_auto)
                AND s.statut NOT IN (:status_closed, :status_archived, :status_refused, :status_draft, :status_draft_archived)
                '.$whereTerritory.'
                '.$wherePartner.'
                GROUP BY su.signalement_id
                HAVING DATEDIFF(NOW(),last_posted_at) > :day_period
                ORDER BY last_posted_at';
    }

    /**
     * @return array<int, int|string>
     */
    public function findSignalementsLastSuiviPublic(
        int $period = Suivi::DEFAULT_PERIOD_RELANCE,
    ): array {
        $connection = $this->getEntityManager()->getConnection();

        $parameters = [
            'day_period' => $period,
            'type_suivi_technical' => Suivi::TYPE_TECHNICAL,
            'status_need_validation' => SignalementStatus::NEED_VALIDATION->value,
            'status_closed' => SignalementStatus::CLOSED->value,
            'status_archived' => SignalementStatus::ARCHIVED->value,
            'status_refused' => SignalementStatus::REFUSED->value,
            'status_draft' => SignalementStatus::DRAFT->value,
            'status_draft_archived' => SignalementStatus::DRAFT_ARCHIVED->value,
        ];

        $sql = 'SELECT s.id, s.created_at, MAX(su.max_date_suivi_technique_or_public) AS last_posted_at
        FROM signalement s
        LEFT JOIN (
            SELECT signalement_id, MAX(created_at) AS max_date_suivi_technique_or_public
            FROM suivi
            WHERE (type = :type_suivi_technical OR is_public = 1)
            GROUP BY signalement_id
        ) su ON s.id = su.signalement_id
        WHERE s.statut NOT IN (:status_need_validation, :status_closed, :status_archived, :status_refused, :status_draft, :status_draft_archived)
        AND s.is_imported != 1
        AND (s.is_usager_abandon_procedure != 1 OR s.is_usager_abandon_procedure IS NULL)
        GROUP BY s.id
        HAVING DATEDIFF(NOW(), IFNULL(last_posted_at, s.created_at)) > :day_period
        ORDER BY last_posted_at
        LIMIT '.$this->limitDailyRelancesByRequest;

        $statement = $connection->prepare($sql);

        return $statement->executeQuery($parameters)->fetchFirstColumn();
    }

    /**
     * @return array<int, int|string>
     */
    public function findSignalementsLastSuiviTechnical(
        int $period = Suivi::DEFAULT_PERIOD_INACTIVITY,
    ): array {
        $connection = $this->getEntityManager()->getConnection();

        $parameters = [
            'day_period' => $period,
            'type_suivi_technical' => Suivi::TYPE_TECHNICAL,
            'status_need_validation' => SignalementStatus::NEED_VALIDATION->value,
            'status_closed' => SignalementStatus::CLOSED->value,
            'status_archived' => SignalementStatus::ARCHIVED->value,
            'status_refused' => SignalementStatus::REFUSED->value,
            'status_draft' => SignalementStatus::DRAFT->value,
            'status_draft_archived' => SignalementStatus::DRAFT_ARCHIVED->value,
        ];

        $sql = 'SELECT s.id
                FROM signalement s
                INNER JOIN (
                    SELECT signalement_id, MAX(created_at) AS max_date_suivi_technique
                    FROM suivi
                    WHERE type = :type_suivi_technical
                    GROUP BY signalement_id
                ) su ON s.id = su.signalement_id
                LEFT JOIN suivi su_last ON su_last.signalement_id = su.signalement_id AND su_last.created_at > su.max_date_suivi_technique
                WHERE su_last.id IS NULL AND su.max_date_suivi_technique < DATE_SUB(NOW(), INTERVAL :day_period DAY)
                AND s.statut NOT IN (:status_need_validation, :status_closed, :status_archived, :status_refused, :status_draft, :status_draft_archived)
                AND s.is_imported != 1
                AND (s.is_usager_abandon_procedure != 1 OR s.is_usager_abandon_procedure IS NULL)
                LIMIT '.$this->limitDailyRelancesByRequest;
        $statement = $connection->prepare($sql);

        return $statement->executeQuery($parameters)->fetchFirstColumn();
    }

    /**
     * @return array<int, int|string>
     */
    public function findSignalementsForThirdRelance(
        int $period = Suivi::DEFAULT_PERIOD_INACTIVITY,
    ): array {
        $connection = $this->getEntityManager()->getConnection();

        $parameters = [
            'type_suivi_technical' => Suivi::TYPE_TECHNICAL,
            'status_need_validation' => SignalementStatus::NEED_VALIDATION->value,
            'status_closed' => SignalementStatus::CLOSED->value,
            'status_archived' => SignalementStatus::ARCHIVED->value,
            'status_refused' => SignalementStatus::REFUSED->value,
            'status_draft' => SignalementStatus::DRAFT->value,
            'status_draft_archived' => SignalementStatus::DRAFT_ARCHIVED->value,
            'nb_suivi_technical' => 2,
        ];

        $sql = $this->getSignalementsLastSuivisTechnicalsQuery(excludeUsagerAbandonProcedure: true, dayPeriod: $period);
        $sql .= ' LIMIT '.$this->limitDailyRelancesByRequest;
        $statement = $connection->prepare($sql);

        return $statement->executeQuery($parameters)->fetchFirstColumn();
    }

    /**
     * @param array<int, Territory> $territories
     *
     * @throws Exception
     */
    public function countSignalementNoSuiviAfter3Relances(
        array $territories,
        ?ArrayCollection $partners = null,
    ): int {
        $connection = $this->getEntityManager()->getConnection();
        $parameters = [
            'type_suivi_technical' => Suivi::TYPE_TECHNICAL,
            'status_need_validation' => SignalementStatus::NEED_VALIDATION->value,
            'status_archived' => SignalementStatus::ARCHIVED->value,
            'status_closed' => SignalementStatus::CLOSED->value,
            'status_refused' => SignalementStatus::REFUSED->value,
            'status_draft' => SignalementStatus::DRAFT->value,
            'status_draft_archived' => SignalementStatus::DRAFT_ARCHIVED->value,
            'nb_suivi_technical' => 3,
        ];

        if (\count($territories)) {
            $parameters['territories'] = implode(',', array_keys($territories));
        }
        if (null !== $partners && !$partners->isEmpty()) {
            $parameters['partners'] = $partners;
            $parameters['status_accepted'] = AffectationStatus::STATUS_ACCEPTED->value;
        }

        $sql = 'SELECT COUNT(*) as count_signalement
                FROM ('.
                        $this->getSignalementsLastSuivisTechnicalsQuery(
                            excludeUsagerAbandonProcedure: false,
                            dayPeriod: 0,
                            partners: $partners,
                            territories: $territories,
                        )
                .') as countSignalementSuivi';
        $statement = $connection->prepare($sql);

        return (int) $statement->executeQuery($parameters)->fetchOne();
    }

    /**
     * @param array<int, Territory> $territories
     */
    public function getSignalementsLastSuivisTechnicalsQuery(
        bool $excludeUsagerAbandonProcedure = true,
        int $dayPeriod = 0,
        ?ArrayCollection $partners = null,
        array $territories = [],
    ): string {
        $joinMaxDateSuivi = $whereTerritory = $wherePartner = $innerPartnerJoin = $whereExcludeUsagerAbandonProcedure
        = $whereLastSuiviDelay = '';

        if (\count($territories)) {
            $whereTerritory = 'AND s.territory_id IN (:territories) ';
        }

        if (null != $partners && !$partners->isEmpty()) {
            $wherePartner = 'AND a.partner_id IN (:partners) ';
            $innerPartnerJoin = 'INNER JOIN affectation a
            ON a.signalement_id = su.signalement_id AND a.statut = :status_accepted ';
        }

        if ($excludeUsagerAbandonProcedure) {
            $whereExcludeUsagerAbandonProcedure = 'AND (s.is_usager_abandon_procedure != 1 OR s.is_usager_abandon_procedure IS NULL)';
        }
        if ($dayPeriod > 0) {
            $whereLastSuiviDelay = 'AND su.max_date_suivi < DATE_SUB(NOW(), INTERVAL '.$dayPeriod.' DAY) ';
        }
        if ($dayPeriod > 0 || (null != $partners && !$partners->isEmpty())) {
            $joinMaxDateSuivi = '
            INNER JOIN (
                SELECT signalement_id, MAX(created_at) AS max_date_suivi
                FROM suivi
                GROUP BY signalement_id
            ) su ON s.id = su.signalement_id ';
        }

        return 'SELECT s.id
                FROM signalement s
                '.$joinMaxDateSuivi.'
                '.$innerPartnerJoin.'
                INNER JOIN (
                    SELECT su.signalement_id, MIN(su.created_at) AS min_date
                    FROM suivi su
                    WHERE su.type = :type_suivi_technical
                    GROUP BY su.signalement_id
                    HAVING COUNT(*) >= :nb_suivi_technical
                ) t1 ON s.id = t1.signalement_id
                LEFT JOIN suivi su2 ON s.id = su2.signalement_id
                AND su2.created_at > t1.min_date
                AND su2.type <> :type_suivi_technical
                WHERE su2.signalement_id IS NULL
                AND s.statut NOT IN (:status_need_validation, :status_closed, :status_archived, :status_refused, :status_draft, :status_draft_archived)
                AND s.is_imported != 1 '
                .$whereLastSuiviDelay
                .$whereExcludeUsagerAbandonProcedure
                .$whereTerritory
                .$wherePartner;
    }

    /**
     * @return array<int, Suivi>
     */
    public function findSuiviByDescription(Signalement $signalement, string $description): array
    {
        $qb = $this->createQueryBuilder('s');
        $qb->where('s.signalement = :signalement')
            ->andWhere('s.description LIKE :description')
            ->setParameter('signalement', $signalement)
            ->setParameter('description', '%'.$description.'%');

        return $qb->getQuery()->getResult();
    }

    /**
     * @return array<int, Suivi>
     */
    public function findSuivisByContext(Signalement $signalement, string $context): array
    {
        $qb = $this->createQueryBuilder('s');
        $qb->where('s.signalement = :signalement')
            ->andWhere('s.context = :context')
            ->orderBy('s.createdAt', 'DESC')
            ->setParameter('signalement', $signalement)
            ->setParameter('context', $context);

        return $qb->getQuery()->getResult();
    }

    /**
     * @return array<int, Suivi>
     *
     * @throws NonUniqueResultException
     */
    public function findAllSuiviBy(Signalement $signalement, int $typeSuivi): array
    {
        $qb = $this->createQueryBuilder('s');
        $qb->where('s.signalement = :signalement')
            ->andWhere('s.type = :type')
            ->orderBy('s.createdAt', 'ASC')
            ->setParameter('signalement', $signalement)
            ->setParameter('type', $typeSuivi);

        return $qb->getQuery()->getResult();
    }

    /**
     * @return array<string, Suivi>
     */
    public function findExistingEventsForSCHS(): array
    {
        $qb = $this->createQueryBuilder('s')
            ->where('s.originalData IS NOT NULL')
            ->andWhere('s.context = :context')
            ->setParameter('context', Suivi::CONTEXT_SCHS);

        $list = $qb->getQuery()->getResult();
        $indexed = [];
        foreach ($list as $suivi) {
            /* @var Suivi $suivi */
            $indexed[$suivi->getOriginalData()['keyDataList'][1]] = $suivi;
        }

        return $indexed;
    }

    public function findLastPublicSuivi(Signalement $signalement, ?User $userToExclude = null): ?Suivi
    {
        $qb = $this->createQueryBuilder('s');
        $qb->where('s.signalement = :signalement')
            ->andWhere('s.isPublic = 1')
            ->andWhere('s.deletedBy IS NULL')
            ->setParameter('signalement', $signalement);
        if (null !== $userToExclude) {
            $qb->andWhere('s.createdBy != :userToExclude')
                ->setParameter('userToExclude', $userToExclude);
        }
        $qb->orderBy('s.createdAt', 'DESC')
            ->setMaxResults(1);

        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * @param array<int, int> $suiviIds
     */
    public function deleteBySuiviIds(array $suiviIds): void
    {
        $qb = $this->createQueryBuilder('s')
            ->delete()
            ->where('s.id in (:suivis)')
            ->setParameter('suivis', $suiviIds);

        $qb->getQuery()->execute();
    }
}
