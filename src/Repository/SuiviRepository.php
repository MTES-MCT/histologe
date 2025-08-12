<?php

namespace App\Repository;

use App\Entity\Enum\AffectationStatus;
use App\Entity\Enum\SignalementStatus;
use App\Entity\Enum\SuiviCategory;
use App\Entity\Enum\UserStatus;
use App\Entity\Signalement;
use App\Entity\Suivi;
use App\Entity\Territory;
use App\Entity\User;
use App\Service\DashboardTabPanel\TabDossier;
use App\Service\DashboardTabPanel\TabQueryParameters;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\DBAL\Exception;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\SecurityBundle\Security;
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
        private readonly Security $security,
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
     *
     * @throws Exception
     */
    public function findSignalementsLastSuiviPublic(
        int $period = Suivi::DEFAULT_PERIOD_RELANCE,
    ): array {
        $connection = $this->getEntityManager()->getConnection();

        $parameters = [
            'day_period' => $period,
            'category_ask_feedback' => SuiviCategory::ASK_FEEDBACK_SENT->value,
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
            WHERE (category = :category_ask_feedback OR is_public = 1)
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
     *
     * @throws Exception
     */
    public function findSignalementsLastAskFeedbackSuiviTechnical(
        int $period = Suivi::DEFAULT_PERIOD_INACTIVITY,
    ): array {
        $connection = $this->getEntityManager()->getConnection();

        $parameters = [
            'day_period' => $period,
            'category_ask_feedback' => SuiviCategory::ASK_FEEDBACK_SENT->value,
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
                    WHERE category = :category_ask_feedback
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
     *
     * @throws Exception
     */
    public function findSignalementsForThirdAskFeedbackRelance(
        int $period = Suivi::DEFAULT_PERIOD_INACTIVITY,
    ): array {
        $connection = $this->getEntityManager()->getConnection();

        $parameters = [
            'category_ask_feedback' => SuiviCategory::ASK_FEEDBACK_SENT->value,
            'status_need_validation' => SignalementStatus::NEED_VALIDATION->value,
            'status_closed' => SignalementStatus::CLOSED->value,
            'status_archived' => SignalementStatus::ARCHIVED->value,
            'status_refused' => SignalementStatus::REFUSED->value,
            'status_draft' => SignalementStatus::DRAFT->value,
            'status_draft_archived' => SignalementStatus::DRAFT_ARCHIVED->value,
            'nb_suivi_technical' => 2,
        ];

        $sql = $this->getSignalementsLastAskFeedbackSuivisQuery(dayPeriod: $period);
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
            'category_ask_feedback' => SuiviCategory::ASK_FEEDBACK_SENT->value,
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
            $parameters['status_accepted'] = AffectationStatus::ACCEPTED->value;
        }

        $sql = 'SELECT COUNT(*) as count_signalement
                FROM ('.
                        $this->getSignalementsLastAskFeedbackSuivisQuery(
                            excludeUsagerAbandonProcedure: false,
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
    public function getSignalementsLastAskFeedbackSuivisQuery(
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
                    WHERE su.category = :category_ask_feedback
                    GROUP BY su.signalement_id
                    HAVING COUNT(*) >= :nb_suivi_technical
                ) t1 ON s.id = t1.signalement_id
                LEFT JOIN suivi su2 ON s.id = su2.signalement_id
                AND su2.created_at > t1.min_date
                AND su2.category <> :category_ask_feedback
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

    /**
     * @throws NonUniqueResultException
     */
    public function findLastPublicSuivi(Signalement $signalement): ?Suivi
    {
        $qb = $this->createQueryBuilder('s');
        $qb->where('s.signalement = :signalement')
            ->andWhere('s.isPublic = 1')
            ->andWhere('s.deletedBy IS NULL')
            ->setParameter('signalement', $signalement)
            ->andWhere('s.category NOT IN (:excludedCategories)')// ignore suivi usager
            ->setParameter('excludedCategories', [SuiviCategory::MESSAGE_USAGER, SuiviCategory::DOCUMENT_DELETED_BY_USAGER]);

        $qb->orderBy('s.createdAt', 'DESC')->setMaxResults(1);

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

    /**
     * @return array<int, array<string, mixed>>
     */
    public function findLastSignalementsWithUserSuivi(User $user, ?Territory $territory, int $limit = 10): array
    {
        $subQb = $this->createQueryBuilder('sq')
            ->select('MAX(sq.createdAt)')
            ->where('sq.signalement = suivi.signalement')
            ->andWhere('sq.createdBy = :user');

        $qb = $this->createQueryBuilder('suivi')
            ->innerJoin('suivi.signalement', 'signalement')
            ->where('suivi.createdBy = :user')
            ->andWhere('signalement.statut NOT IN (:excludedStatus)')
            ->andWhere('suivi.createdAt = ('.$subQb->getDQL().')')
            ->setParameter('user', $user)
            ->setParameter('excludedStatus', [
                SignalementStatus::ARCHIVED->value,
                SignalementStatus::DRAFT->value,
                SignalementStatus::DRAFT_ARCHIVED->value,
            ])
            ->orderBy('suivi.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->select('
                signalement.reference AS reference,
                signalement.nomOccupant AS nomOccupant,
                signalement.prenomOccupant AS prenomOccupant,
                CONCAT(signalement.adresseOccupant, \' \', signalement.cpOccupant, \' \', signalement.villeOccupant) AS adresseOccupant,
                signalement.uuid AS uuid,
                signalement.statut AS statut,
                suivi.createdAt AS suiviCreatedAt,
                suivi.category AS suiviCategory,
                suivi.isPublic AS suiviIsPublic,
                (
                    SELECT CASE WHEN MAX(s2.createdAt) > suivi.createdAt THEN 1 ELSE 0 END
                    FROM '.Suivi::class.' s2
                    WHERE s2.signalement = signalement
                ) AS hasNewerSuivi
            ');
        if (null !== $territory) {
            $qb->andWhere('signalement.territory = :territory')
                ->setParameter('territory', $territory);
        }

        return $qb->getQuery()->getArrayResult();
    }

    /**
     * @return array<array{id: int, signalement_id: int, created_at: string}>
     */
    public function findWithUnarchivedRtDistinctByUserAndSignalement(): ?array
    {
        $sql = "
            SELECT u.id, s.signalement_id, MIN(s.created_at) AS created_at
            FROM suivi s
            INNER JOIN user u ON u.id = s.created_by_id
            WHERE u.statut != '".UserStatus::ARCHIVE->value."'
            AND (JSON_CONTAINS(u.roles, '\"ROLE_ADMIN_TERRITORY\"') = 1)
            GROUP BY u.id, s.signalement_id
        ";

        $connection = $this->getEntityManager()->getConnection();
        $stmt = $connection->prepare($sql);

        return $stmt->executeQuery()->fetchAllAssociative();
    }

    private function buildBaseQb(?TabQueryParameters $params, SuiviCategory $category): QueryBuilder
    {
        /** @var User $user */
        $user = $this->security->getUser();

        $subQb = $this->_em->createQueryBuilder()
            ->select('MAX(s2.createdAt)')
            ->from(Suivi::class, 's2')
            ->where('s2.signalement = signalement');

        $qb = $this->createQueryBuilder('s')
            ->leftJoin('s.signalement', 'signalement')
            ->leftJoin('s.createdBy', 'user')
            ->where('s.category = :category')
            ->setParameter('category', $category)
            ->andWhere('s.createdAt = ('.$subQb->getDQL().')');

        if ($params?->territoireId) {
            $qb
                ->andWhere('signalement.territory = :territoireId')
                ->setParameter('territoireId', $params->territoireId);
        } elseif (!$user->isSuperAdmin()) {
            $qb
                ->andWhere('signalement.territory IN (:territories)')
                ->setParameter('territories', $user->getPartnersTerritories());
        }

        if ($params?->mesDossiersMessagesUsagers && '1' === $params->mesDossiersMessagesUsagers) {
            $qb
                ->innerJoin('signalement.userSignalementSubscriptions', 'uss')
                ->andWhere('uss.user = :currentUser')
                ->setParameter('currentUser', $user);
        }

        return $qb;
    }

    private function addSelectAndOrder(QueryBuilder $qb, ?TabQueryParameters $params, bool $countOnly = false): QueryBuilder
    {
        if ($countOnly) {
            $qb->select('COUNT(DISTINCT signalement.id)');

            return $qb;
        }

        $qb->select(
            'signalement.uuid AS uuid',
            'signalement.nomOccupant AS nomOccupant',
            'signalement.prenomOccupant AS prenomOccupant',
            'signalement.reference AS reference',
            "CONCAT_WS(', ', signalement.adresseOccupant, CONCAT(signalement.cpOccupant, ' ', signalement.villeOccupant)) AS adresse",
            's.createdAt AS messageAt',
            'DATE_DIFF(CURRENT_DATE(), s.createdAt) AS messageDaysAgo',
            'signalement.closedAt AS clotureAt',
            'user.nom AS messageSuiviByNom',
            'user.prenom AS messageSuiviByPrenom',
            "CASE
                WHEN user.email = signalement.mailOccupant THEN 'OCCUPANT'
                WHEN user.email = signalement.mailDeclarant THEN 'TIERS DECLARANT'
                ELSE 'OCCUPANT OU DECLARANT'
            END AS messageByProfileDeclarant"
        );

        if ($params && in_array($params->sortBy, ['createdAt'], true)
            && in_array($params->orderBy, ['ASC', 'DESC', 'asc', 'desc'], true)
        ) {
            $qb->orderBy('s.'.$params->sortBy, $params->orderBy);
        } else {
            $qb->orderBy('s.createdAt', 'ASC');
        }

        $qb->setMaxResults(TabDossier::MAX_ITEMS_LIST);

        return $qb;
    }

    private function addDernierSuiviFilter(QueryBuilder $qb, bool $avantDernierEstAskFeedback): QueryBuilder
    {
        $existsCondition = $avantDernierEstAskFeedback
        ? 'EXISTS'
        : 'NOT EXISTS';

        $qb->andWhere("$existsCondition (
            SELECT 1
            FROM App\Entity\Suivi s3
            WHERE s3.signalement = signalement
            AND s3.category = :askFeedbackCategory
            AND s3.createdAt = (
                SELECT MAX(s4.createdAt)
                FROM App\Entity\Suivi s4
                WHERE s4.signalement = signalement
                AND s4.createdAt < s.createdAt
            )
        )")
        ->setParameter('askFeedbackCategory', SuiviCategory::ASK_FEEDBACK_SENT)
        ->andWhere('s.type != :type')
        ->setParameter('type', Suivi::TYPE_USAGER_POST_CLOTURE)// TODO : à supprimer quand on aura une catégorie MESSAGE_USAGER_POST_CLOTURE
        ->andWhere('signalement.statut = :statut')
        ->setParameter('statut', SignalementStatus::ACTIVE);

        return $qb;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getLastMessageUsagerWithoutAskFeedbackBefore(?TabQueryParameters $params): array
    {
        $qb = $this->buildBaseQb($params, SuiviCategory::MESSAGE_USAGER);
        $qb = $this->addDernierSuiviFilter($qb, false);
        $qb = $this->addSelectAndOrder($qb, $params);

        return $qb->getQuery()->getResult();
    }

    public function countLastMessageUsagerWithoutAskFeedbackBefore(?TabQueryParameters $params): int
    {
        $qb = $this->buildBaseQb($params, SuiviCategory::MESSAGE_USAGER);
        $qb = $this->addDernierSuiviFilter($qb, false);
        $qb = $this->addSelectAndOrder($qb, $params, true);

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getLastMessageUsagerIsPostCloture(?TabQueryParameters $params): array
    {
        $qb = $this->buildBaseQb($params, SuiviCategory::MESSAGE_USAGER); // TODO : utiliser category MESSAGE_USAGER_POST_CLOTURE cf #4471
        $qb->andWhere('s.type = :type')
            ->setParameter('type', Suivi::TYPE_USAGER_POST_CLOTURE); // TODO : à supprimer quand on aura une catégorie MESSAGE_USAGER_POST_CLOTURE

        $qb->andWhere('signalement.statut = :statut')
            ->setParameter('statut', SignalementStatus::CLOSED);
        $qb = $this->addSelectAndOrder($qb, $params);

        return $qb->getQuery()->getResult();
    }

    public function countLastMessageUsagerIsPostCloture(?TabQueryParameters $params): int
    {
        $qb = $this->buildBaseQb($params, SuiviCategory::MESSAGE_USAGER); // TODO : utiliser category MESSAGE_USAGER_POST_CLOTURE cf #4471
        $qb->andWhere('s.type = :type')
            ->setParameter('type', Suivi::TYPE_USAGER_POST_CLOTURE); // TODO : à supprimer quand on aura une catégorie MESSAGE_USAGER_POST_CLOTURE
        $qb->andWhere('signalement.statut = :statut')
            ->setParameter('statut', SignalementStatus::CLOSED);
        $qb = $this->addSelectAndOrder($qb, $params, true);

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getLastMessageUsagerWithAskFeedbackBefore(?TabQueryParameters $params): array
    {
        $qb = $this->buildBaseQb($params, SuiviCategory::MESSAGE_USAGER);
        // TODO : ajouter Les dossiers où l’usager a indiqué vouloir poursuivre sa procédure
        $qb = $this->addDernierSuiviFilter($qb, true);
        $qb = $this->addSelectAndOrder($qb, $params);

        return $qb->getQuery()->getResult();
    }

    public function countLastMessageUsagerWithAskFeedbackBefore(?TabQueryParameters $params): int
    {
        $qb = $this->buildBaseQb($params, SuiviCategory::MESSAGE_USAGER);
        // TODO : ajouter Les dossiers où l’usager a indiqué vouloir poursuivre sa procédure
        $qb = $this->addDernierSuiviFilter($qb, true);
        $qb = $this->addSelectAndOrder($qb, $params, true);

        return (int) $qb->getQuery()->getSingleScalarResult();
    }
}
