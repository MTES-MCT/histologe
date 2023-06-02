<?php

namespace App\Repository;

use App\Entity\Enum\AffectationStatus;
use App\Entity\Partner;
use App\Entity\Signalement;
use App\Entity\Suivi;
use App\Entity\Territory;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Exception;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Suivi|null find($id, $lockMode = null, $lockVersion = null)
 * @method Suivi|null findOneBy(array $criteria, array $orderBy = null)
 * @method Suivi[]    findAll()
 * @method Suivi[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SuiviRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Suivi::class);
    }

    /**
     * @throws Exception
     */
    public function getAverageSuivi(?Territory $territory = null): float
    {
        $connection = $this->getEntityManager()->getConnection();
        $whereTerritory = $territory instanceof Territory ? 'AND s.territory_id = :territory_id' : null;
        $parameters['statut'] = Signalement::STATUS_ARCHIVED;

        if (null !== $whereTerritory) {
            $parameters['territory_id'] = $territory->getId();
        }

        $sql = 'SELECT AVG(nb_suivi) as average_nb_suivi
                FROM (
                    SELECT su.signalement_id, s.uuid, count(*) as nb_suivi
                    FROM suivi su
                    INNER JOIN signalement s on s.id = su.signalement_id
                    WHERE s.statut != :statut
                    '.$whereTerritory.'
                    GROUP BY su.signalement_id
                ) as countQuery';

        $statement = $connection->prepare($sql);

        return (float) $statement->executeQuery($parameters)->fetchOne();
    }

    /**
     * @throws Exception
     */
    public function countSignalementNoSuiviSince(
        int $period = Suivi::DEFAULT_PERIOD_INACTIVITY,
        ?Territory $territory = null,
        ?Partner $partner = null,
    ): int {
        $connection = $this->getEntityManager()->getConnection();
        $parameters = [
            'day_period' => $period,
            'type_suivi_usager' => Suivi::TYPE_USAGER,
            'type_suivi_partner' => Suivi::TYPE_PARTNER,
            'type_suivi_auto' => Suivi::TYPE_AUTO,
            'status_archived' => Signalement::STATUS_ARCHIVED,
            'status_closed' => Signalement::STATUS_CLOSED,
        ];

        if (null !== $territory) {
            $parameters['territory_id'] = $territory->getId();
        }
        if (null !== $partner) {
            $parameters['partner_id'] = $partner->getId();
            $parameters['status_wait'] = AffectationStatus::STATUS_WAIT->value;
            $parameters['status_accepted'] = AffectationStatus::STATUS_ACCEPTED->value;
        }

        $sql = 'SELECT COUNT(*) as count_signalement
                FROM ('.
                        $this->getSignalementsQuery($territory, $partner)
                .') as countSignalementSuivi';
        $statement = $connection->prepare($sql);

        return (int) $statement->executeQuery($parameters)->fetchOne();
    }

    /**
     * @throws Exception
     */
    public function findSignalementNoSuiviSince(
        int $period = Suivi::DEFAULT_PERIOD_INACTIVITY,
        ?Territory $territory = null,
        ?Partner $partner = null,
    ): array {
        $connection = $this->getEntityManager()->getConnection();
        $parameters = [
            'day_period' => $period,
            'type_suivi_usager' => Suivi::TYPE_USAGER,
            'type_suivi_partner' => Suivi::TYPE_PARTNER,
            'type_suivi_auto' => Suivi::TYPE_AUTO,
            'status_archived' => Signalement::STATUS_ARCHIVED,
            'status_closed' => Signalement::STATUS_CLOSED,
        ];

        if (null !== $territory) {
            $parameters['territory_id'] = $territory->getId();
        }

        if (null != $partner) {
            $parameters['partner_id'] = $partner->getId();
            $parameters['status_wait'] = AffectationStatus::STATUS_WAIT->value;
            $parameters['status_accepted'] = AffectationStatus::STATUS_ACCEPTED->value;
        }

        $sql = $this->getSignalementsQuery($territory, $partner);
        $statement = $connection->prepare($sql);

        return $statement->executeQuery($parameters)->fetchFirstColumn();
    }

    /**
     * @throws NonUniqueResultException
     * @throws NoResultException
     */
    public function countSuiviPartner(?Territory $territory = null): int
    {
        $qb = $this->createQueryBuilder('s');
        $qb->select('COUNT(s) as nb_suivi')
            ->innerJoin('s.signalement', 'sig')
            ->where('sig.statut != :statut')
            ->andWhere('s.type = :type_suivi')
            ->setParameter('statut', Signalement::STATUS_ARCHIVED)
            ->setParameter('type_suivi', Suivi::TYPE_PARTNER);

        if ($territory instanceof Territory) {
            $qb->andWhere('sig.territory = :territory')->setParameter('territory', $territory);
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * @throws NonUniqueResultException
     * @throws NoResultException
     */
    public function countSuiviUsager(?Territory $territory = null): int
    {
        $qb = $this->createQueryBuilder('s');
        $qb->select('COUNT(s) as nb_suivi')
            ->innerJoin('s.signalement', 'sig')
            ->leftJoin('s.createdBy', 'u')
            ->where('sig.statut != :statut')
            ->andWhere('s.type = :type_suivi')
            ->setParameter('type_suivi', Suivi::TYPE_USAGER)
            ->setParameter('statut', Signalement::STATUS_ARCHIVED);

        if ($territory instanceof Territory) {
            $qb->andWhere('sig.territory = :territory')->setParameter('territory', $territory);
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    private function getSignalementsQuery(
        ?Territory $territory = null,
        ?Partner $partner = null
    ): string {
        $whereTerritory = $wherePartner = $innerPartnerJoin = '';

        if (null !== $territory) {
            $whereTerritory = 'AND s.territory_id = :territory_id';
        }

        if (null != $partner) {
            $wherePartner = 'AND a.partner_id = :partner_id';
            $innerPartnerJoin = 'INNER JOIN affectation a ON a.signalement_id = su.signalement_id AND a.statut IN (:status_wait, :status_accepted)';
        }

        return 'SELECT su.signalement_id, MAX(su.created_at) as last_posted_at
                FROM suivi su
                INNER JOIN signalement s on s.id = su.signalement_id
                '.$innerPartnerJoin.'
                WHERE type in (:type_suivi_usager,:type_suivi_partner, :type_suivi_auto)
                AND s.statut NOT IN (:status_closed, :status_archived)
                '.$whereTerritory.'
                '.$wherePartner.'
                GROUP BY su.signalement_id
                HAVING DATEDIFF(NOW(),last_posted_at) > :day_period
                ORDER BY last_posted_at';
    }

    public function findSignalementsLastSuiviPublic(
        int $period = Suivi::DEFAULT_PERIOD_RELANCE,
    ): array {
        $connection = $this->getEntityManager()->getConnection();

        $parameters = [
            'day_period' => $period,
            'type_suivi_technical' => Suivi::TYPE_TECHNICAL,
            'status_need_validation' => Signalement::STATUS_NEED_VALIDATION,
            'status_closed' => Signalement::STATUS_CLOSED,
            'status_archived' => Signalement::STATUS_ARCHIVED,
            'status_refused' => Signalement::STATUS_REFUSED,
        ];

        $sql = 'SELECT su.signalement_id, MAX(su.created_at) as last_posted_at
        FROM suivi su
        INNER JOIN signalement s on s.id = su.signalement_id
        WHERE (su.type = :type_suivi_technical OR su.is_public = 1)
        AND s.statut NOT IN (:status_need_validation, :status_closed, :status_archived, :status_refused)
        AND s.is_imported != 1
        AND s.is_usager_abandon_procedure != 1
        GROUP BY su.signalement_id
        HAVING DATEDIFF(NOW(),last_posted_at) > :day_period
        ORDER BY last_posted_at';

        $statement = $connection->prepare($sql);

        return $statement->executeQuery($parameters)->fetchFirstColumn();
    }

    public function findSignalementsLastSuiviTechnical(
        int $period = Suivi::DEFAULT_PERIOD_INACTIVITY,
    ): array {
        $connection = $this->getEntityManager()->getConnection();

        $parameters = [
            'day_period' => $period,
            'type_suivi_technical' => Suivi::TYPE_TECHNICAL,
            'status_need_validation' => Signalement::STATUS_NEED_VALIDATION,
            'status_closed' => Signalement::STATUS_CLOSED,
            'status_archived' => Signalement::STATUS_ARCHIVED,
            'status_refused' => Signalement::STATUS_REFUSED,
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
        AND s.statut NOT IN (:status_need_validation, :status_closed, :status_archived, :status_refused)
        AND s.is_imported != 1
        AND s.is_usager_abandon_procedure != 1';

        $statement = $connection->prepare($sql);

        return $statement->executeQuery($parameters)->fetchFirstColumn();
    }

    public function findSignalementsThirdRelance(
        int $period = Suivi::DEFAULT_PERIOD_INACTIVITY,
    ): array {
        $connection = $this->getEntityManager()->getConnection();

        $parameters = [
            'day_period' => $period,
            'type_suivi_technical' => Suivi::TYPE_TECHNICAL,
            'status_need_validation' => Signalement::STATUS_NEED_VALIDATION,
            'status_closed' => Signalement::STATUS_CLOSED,
            'status_archived' => Signalement::STATUS_ARCHIVED,
            'status_refused' => Signalement::STATUS_REFUSED,
            'nb_suivi_technical' => 2,
        ];

        $sql = $this->getSignalements3LastSuivisTechnicalsQuery(null, null, true);

        $statement = $connection->prepare($sql);

        return $statement->executeQuery($parameters)->fetchFirstColumn();
    }

    /**
     * @throws Exception
     */
    public function countSignalement3LastSuivisTechnicals(
        ?Territory $territory = null,
        ?Partner $partner = null,
    ): int {
        $connection = $this->getEntityManager()->getConnection();
        $parameters = [
            'day_period' => 0,
            'type_suivi_technical' => Suivi::TYPE_TECHNICAL,
            'status_need_validation' => Signalement::STATUS_NEED_VALIDATION,
            'status_archived' => Signalement::STATUS_ARCHIVED,
            'status_closed' => Signalement::STATUS_CLOSED,
            'status_refused' => Signalement::STATUS_REFUSED,
            'nb_suivi_technical' => 3,
        ];

        if (null !== $territory) {
            $parameters['territory_id'] = $territory->getId();
        }
        if (null !== $partner) {
            $parameters['partner_id'] = $partner->getId();
            $parameters['status_wait'] = AffectationStatus::STATUS_WAIT->value;
            $parameters['status_accepted'] = AffectationStatus::STATUS_ACCEPTED->value;
        }

        $sql = 'SELECT COUNT(*) as count_signalement
                FROM ('.
                        $this->getSignalements3LastSuivisTechnicalsQuery($territory, $partner, false)
                .') as countSignalementSuivi';
        $statement = $connection->prepare($sql);

        return (int) $statement->executeQuery($parameters)->fetchOne();
    }

    private function getSignalements3LastSuivisTechnicalsQuery(
        ?Territory $territory = null,
        ?Partner $partner = null,
        bool $excludeUsagerAbandonProcedure = true,
    ): string {
        $whereTerritory = $wherePartner = $innerPartnerJoin = $whereExcludeUsagerAbandonProcedure = '';

        if (null !== $territory) {
            $whereTerritory = 'AND s.territory_id = :territory_id';
        }

        if (null != $partner) {
            $wherePartner = 'AND a.partner_id = :partner_id';
            $innerPartnerJoin = 'INNER JOIN affectation a ON a.signalement_id = su.signalement_id AND a.statut IN (:status_wait, :status_accepted)';
        }

        if ($excludeUsagerAbandonProcedure) {
            $whereExcludeUsagerAbandonProcedure = 'AND s.is_usager_abandon_procedure != 1';
        }

        return 'SELECT s.id
                FROM signalement s
                INNER JOIN (
                    SELECT signalement_id, MAX(created_at) AS max_date_suivi
                    FROM suivi
                    GROUP BY signalement_id
                ) su ON s.id = su.signalement_id
                INNER JOIN (
                SELECT su.signalement_id
                FROM suivi su
                WHERE su.type = :type_suivi_technical
                GROUP BY su.signalement_id
                HAVING COUNT(*) >= :nb_suivi_technical
                ) t1 ON s.id = t1.signalement_id
                LEFT JOIN (
                SELECT su.signalement_id
                FROM suivi su
                WHERE su.type <> :type_suivi_technical
                    AND su.created_at > (
                    SELECT MIN(su2.created_at)
                    FROM suivi su2
                    WHERE su2.signalement_id = su.signalement_id
                        AND su2.type = :type_suivi_technical
                    )
                ) t2 ON s.id = t2.signalement_id
                        '.$innerPartnerJoin.'
                WHERE t2.signalement_id IS NULL
                AND su.max_date_suivi < DATE_SUB(NOW(), INTERVAL :day_period DAY)
                AND s.statut NOT IN (:status_need_validation, :status_closed, :status_archived, :status_refused)
                AND s.is_imported != 1
                '.$whereExcludeUsagerAbandonProcedure.'
                '.$whereTerritory.'
                '.$wherePartner;
    }
}
