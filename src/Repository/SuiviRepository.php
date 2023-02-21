<?php

namespace App\Repository;

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
        $whereTerritory = $wherePartner = '';
        $innerSignalementJoin = $innerPartnerJoin = '';
        $parameters['day_period'] = $period;
        $parameters['type_suivi_usager'] = Suivi::TYPE_USAGER;
        $parameters['type_suivi_partner'] = Suivi::TYPE_PARTNER;

        if (null !== $territory) {
            $whereTerritory = 'AND si.territory_id = :territory_id';
            $innerSignalementJoin = 'INNER JOIN signalement si ON si.id = su.signalement_id ';
            $parameters['territory_id'] = $territory->getId();
        }

        if (null !== $partner) {
            $wherePartner = 'AND a.partner_id = :partner_id';
            $innerPartnerJoin = 'INNER JOIN affectation a ON a.signalement_id = su.signalement_id';
            $parameters['partner_id'] = $partner->getId();
        }

        $sql = 'SELECT COUNT(*) as count_signalement
                FROM (
                    SELECT su.signalement_id, MAX(su.created_at) as last_posted_at
                    FROM suivi su
                    '.$innerSignalementJoin.'
                    '.$innerPartnerJoin.'
                    WHERE type in (:type_suivi_usager,:type_suivi_partner)
                    '.$whereTerritory.'
                    '.$wherePartner.'
                    GROUP BY su.signalement_id
                    HAVING DATEDIFF(NOW(),last_posted_at) > :day_period
                    ORDER BY last_posted_at
                ) as countSignalementSuivi';

        $statement = $connection->prepare($sql);

        return (int) $statement->executeQuery($parameters)->fetchOne();
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
}
