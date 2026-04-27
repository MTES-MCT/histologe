<?php

namespace App\Repository;

use App\Entity\Enum\SignalementStatus;
use App\Entity\Enum\SuiviCategory;
use App\Entity\Partner;
use App\Entity\Signalement;
use App\Entity\Suivi;
use App\Entity\Territory;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\DBAL\Exception;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Clock\ClockInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * @extends ServiceEntityRepository<Suivi>
 *
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
        #[Autowire(env: 'DELAY_SUIVI_EDITABLE_IN_MINUTES')]
        private readonly int $delaySuiviEditableInMinutes,
        private ClockInterface $clock,
    ) {
        parent::__construct($registry, Suivi::class);
    }

    /**
     * @return array<int, int|string>
     *
     * @throws Exception
     */
    public function findSignalementsForFirstAskFeedbackRelance(
        int $period = Suivi::DEFAULT_PERIOD_RELANCE,
    ): array {
        // - dernier suivi public > 45 jours (Suivi::DEFAULT_PERIOD_RELANCE)
        // - zéro ASK_FEEDBACK_SENT depuis ce dernier suivi public
        // - statut actif
        // - non importé
        // - non vacant
        // - pas de demande d'abandon de procédure
        $connection = $this->getEntityManager()->getConnection();

        $parameters = [
            'day_period' => $period,
            'category_ask_feedback' => SuiviCategory::ASK_FEEDBACK_SENT->value,
            'status_active' => SignalementStatus::ACTIVE->value,
        ];
        $sql = 'SELECT s.id, s.created_at
        FROM signalement s
        JOIN (
            SELECT signalement_id, MAX(created_at) AS last_public
            FROM suivi
            WHERE suivi.isVisibleForUsager = 1
            GROUP BY signalement_id
        ) pub ON pub.signalement_id = s.id
        LEFT JOIN (
            SELECT signalement_id, MAX(created_at) AS last_af
            FROM suivi
            WHERE category = :category_ask_feedback
            GROUP BY signalement_id
        ) af ON af.signalement_id = s.id
        WHERE pub.last_public < DATE_SUB(NOW(), INTERVAL :day_period DAY)
        AND (af.last_af IS NULL OR af.last_af < pub.last_public)
        AND s.statut = :status_active
        AND s.is_imported != 1
        AND (s.is_logement_vacant IS NULL OR s.is_logement_vacant = 0)
        AND (s.is_usager_abandon_procedure != 1 OR s.is_usager_abandon_procedure IS NULL)
        LIMIT '.$this->limitDailyRelancesByRequest;

        $statement = $connection->prepare($sql);

        return $statement->executeQuery($parameters)->fetchFirstColumn();
    }

    /**
     * @return array<int, int|string>
     *
     * @throws Exception
     */
    public function findSignalementsForSecondAskFeedbackRelance(
        int $period = Suivi::DEFAULT_PERIOD_INACTIVITY,
    ): array {
        // - dernier suivi public > 45 (Suivi::DEFAULT_PERIOD_RELANCE) + 30 (Suivi::DEFAULT_PERIOD_INACTIVITY) jours
        // - 1 et un seul ASK_FEEDBACK_SENT depuis ce dernier suivi public > 30 jours (Suivi::DEFAULT_PERIOD_INACTIVITY)
        // - statut actif
        // - non importé
        // - non vacant
        // - pas de demande d'abandon de procédure
        $connection = $this->getEntityManager()->getConnection();

        $parameters = [
            'day_period' => $period,
            'category_ask_feedback' => SuiviCategory::ASK_FEEDBACK_SENT->value,
            'status_active' => SignalementStatus::ACTIVE->value,
        ];
        $sql = 'SELECT s.id
                FROM signalement s
                JOIN (
                    -- dernier suivi public
                    SELECT signalement_id, MAX(created_at) AS last_public
                    FROM suivi
                    WHERE suivi.isVisibleForUsager = 1
                    GROUP BY signalement_id
                ) pub ON pub.signalement_id = s.id
                JOIN (
                    -- compter le nombre de suivis ASK_FEEDBACK depuis le dernier public
                    SELECT su.signalement_id, COUNT(*) AS nb_af, MAX(su.created_at) AS last_af
                    FROM suivi su
                    JOIN (
                        SELECT signalement_id, MAX(created_at) AS last_public
                        FROM suivi
                        WHERE suivi.isVisibleForUsager = 1
                        GROUP BY signalement_id
                    ) pub2 ON pub2.signalement_id = su.signalement_id
                    WHERE su.category = :category_ask_feedback
                    AND su.created_at > pub2.last_public
                    GROUP BY su.signalement_id
                    HAVING nb_af = 1 AND last_af < DATE_SUB(NOW(), INTERVAL :day_period DAY)
                ) af ON af.signalement_id = s.id
                WHERE s.statut = :status_active
                AND s.is_imported != 1
                AND (s.is_logement_vacant IS NULL OR s.is_logement_vacant = 0)
                AND (s.is_usager_abandon_procedure != 1 OR s.is_usager_abandon_procedure IS NULL)
                LIMIT '.$this->limitDailyRelancesByRequest;

        $statement = $connection->prepare($sql);

        return $statement->executeQuery($parameters)->fetchFirstColumn();
    }

    /**
     * @return array<int, int|string>
     *
     * @throws Exception
     */
    public function findSignalementsForThirdAskFeedbackRelance(
        int $period = Suivi::DEFAULT_PERIOD_INACTIVITY,
    ): array {
        // - 2 et seulement 2 ASK_FEEDBACK_SENT depuis le dernier suivi public (dont le dernier) > 30 jours (Suivi::DEFAULT_PERIOD_INACTIVITY)
        // - statut actif
        // - non importé
        // - non vacant
        // - pas de demande d'abandon de procédure
        $connection = $this->getEntityManager()->getConnection();

        $parameters = [
            'category_ask_feedback' => SuiviCategory::ASK_FEEDBACK_SENT->value,
            'status_active' => SignalementStatus::ACTIVE->value,
        ];

        $sql = $this->getSignalementsLastAskFeedbackSuivisQuery(
            dayPeriod: $period,
            exactCount: 2
        );

        $sql .= ' LIMIT '.$this->limitDailyRelancesByRequest;
        $statement = $connection->prepare($sql);

        return $statement->executeQuery($parameters)->fetchFirstColumn();
    }

    /**
     * @return array<int, int|string>
     *
     * @throws Exception
     */
    public function findSignalementsForLoopAskFeedbackRelance(
        int $loopDelay = Suivi::DEFAULT_PERIOD_BOUCLE,
    ): array {
        // - au moins 3 ASK_FEEDBACK_SENT depuis le dernier suivi public (dont le dernier) > 90 jours (Suivi::DEFAULT_PERIOD_BOUCLE)
        // - statut actif
        // - non importé
        // - non vacant
        // - pas de demande d'abandon de procédure
        $connection = $this->getEntityManager()->getConnection();

        $parameters = [
            'category_ask_feedback' => SuiviCategory::ASK_FEEDBACK_SENT->value,
            'status_active' => SignalementStatus::ACTIVE->value,
            'nb_suivi_technical' => 3,
        ];

        $sql = $this->getSignalementsLastAskFeedbackSuivisQuery(
            dayPeriod: $loopDelay
        );

        $sql .= ' LIMIT '.$this->limitDailyRelancesByRequest;

        $statement = $connection->prepare($sql);

        return $statement->executeQuery($parameters)->fetchFirstColumn();
    }

    /**
     * @param array<int, Territory>          $territories
     * @param ?ArrayCollection<int, Partner> $partners
     */
    public function getSignalementsLastAskFeedbackSuivisQuery(
        bool $excludeUsagerAbandonProcedure = true,
        int $dayPeriod = 0,
        ?ArrayCollection $partners = null,
        array $territories = [],
        ?int $exactCount = null,
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

        $havingClause = null !== $exactCount
            ? 'HAVING COUNT(*) = '.$exactCount
            : 'HAVING COUNT(*) >= :nb_suivi_technical';

        return 'SELECT s.id
                FROM signalement s
                '.$joinMaxDateSuivi.'
                '.$innerPartnerJoin.'
                INNER JOIN (
                    SELECT su.signalement_id, MIN(su.created_at) AS min_date
                    FROM suivi su
                    INNER JOIN signalement s2 ON s2.id = su.signalement_id
                    WHERE su.category = :category_ask_feedback
                    AND su.created_at > COALESCE(
                        (SELECT MAX(created_at) FROM suivi WHERE signalement_id = su.signalement_id AND suivi.isVisibleForUsager = 1),
                        s2.created_at
                    )
                    GROUP BY su.signalement_id
                    '.$havingClause.'
                ) t1 ON s.id = t1.signalement_id
                LEFT JOIN suivi su2 ON s.id = su2.signalement_id
                AND su2.created_at > t1.min_date
                AND su2.category <> :category_ask_feedback
                WHERE su2.signalement_id IS NULL
                AND s.statut = :status_active
                AND s.is_imported != 1 
                AND (s.is_logement_vacant IS NULL OR s.is_logement_vacant = 0)'
                .$whereLastSuiviDelay
                .$whereExcludeUsagerAbandonProcedure
                .$whereTerritory
                .$wherePartner;
    }

    /**
     * @return array<int, Suivi>
     */
    public function findSuiviByDescription(
        Signalement $signalement,
        string $description,
        ?SuiviCategory $suiviCategory = null,
    ): array {
        $qb = $this->createQueryBuilder('s');
        $qb->where('s.signalement = :signalement')
            ->andWhere('s.description LIKE :description')
            ->setParameter('signalement', $signalement)
            ->setParameter('description', '%'.$description.'%');

        if (null !== $suiviCategory) {
            $qb
                ->andWhere('s.category = :category')
                ->setParameter('category', SuiviCategory::SIGNALEMENT_STATUS_IS_SYNCHRO);
        }

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

    /**
     * @throws NonUniqueResultException
     */
    public function findLastPublicSuivi(Signalement $signalement): ?Suivi
    {
        $qb = $this->createQueryBuilder('s');
        $qb->where('s.signalement = :signalement')
            ->andWhere('s.isVisibleForUsager = 1')
            ->andWhere('s.deletedBy IS NULL')
            ->setParameter('signalement', $signalement)
            ->andWhere('s.category NOT IN (:excludedCategories)')// ignore suivi usager
            ->setParameter('excludedCategories', [SuiviCategory::MESSAGE_USAGER, SuiviCategory::MESSAGE_USAGER_POST_CLOTURE, SuiviCategory::DOCUMENT_DELETED_BY_USAGER, SuiviCategory::SIGNALEMENT_EDITED_FO]);

        $qb->orderBy('s.createdAt', 'DESC')->setMaxResults(1);

        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * @return array<int, Suivi>
     */
    public function findWithWaitingNotificationAndExpiredDelay(): array
    {
        $limit = $this->clock->now()->modify('-'.$this->delaySuiviEditableInMinutes.' minutes');
        $qb = $this->createQueryBuilder('s');
        $qb->where('s.createdAt < :limit')
            ->setParameter('limit', $limit)
            ->andWhere('s.waitingNotification = 1');

        return $qb->getQuery()->getResult();
    }
}
