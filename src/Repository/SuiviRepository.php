<?php

namespace App\Repository;

use App\Entity\Signalement;
use App\Entity\Suivi;
use App\Entity\Territory;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Exception;
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
}
