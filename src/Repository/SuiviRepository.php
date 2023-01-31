<?php

namespace App\Repository;

use App\Entity\Signalement;
use App\Entity\Suivi;
use App\Entity\Territory;
use App\Entity\User;
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
     * @throws NonUniqueResultException
     * @throws NoResultException
     */
    public function countSuiviPartner(?Territory $territory = null): int
    {
        $qb = $this->createQueryBuilder('s');
        $qb->select('COUNT(s) as nb_suivi')
            ->innerJoin('s.signalement', 'sig')
            ->innerJoin('s.createdBy', 'u')
            ->where('sig.statut != :statut')
            ->andWhere(
                $qb->expr()->orX(
                    $qb->expr()->like('u.roles', ':role1'),
                    $qb->expr()->like('u.roles', ':role2'),
                    $qb->expr()->like('u.roles', ':role3')
                )
            )
            ->setParameter('role1', '%'.User::ROLE_ADMIN_TERRITORY.'%')
            ->setParameter('role2', '%'.User::ROLE_ADMIN_PARTNER.'%')
            ->setParameter('role3', '%'.User::ROLE_USER_PARTNER.'%')
            ->setParameter('statut', Signalement::STATUS_ARCHIVED);

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
            ->andWhere(
                $qb->expr()->orX(
                    $qb->expr()->like('u.roles', ':role'),
                    $qb->expr()->isNull('s.createdBy')
                )
            )
            ->setParameter('role', '%'.User::ROLE_USAGER.'%')
            ->setParameter('statut', Signalement::STATUS_ARCHIVED);

        if ($territory instanceof Territory) {
            $qb->andWhere('sig.territory = :territory')->setParameter('territory', $territory);
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }
}
