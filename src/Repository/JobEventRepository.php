<?php

namespace App\Repository;

use App\Entity\JobEvent;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Exception;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<JobEvent>
 *
 * @method JobEvent|null find($id, $lockMode = null, $lockVersion = null)
 * @method JobEvent|null findOneBy(array $criteria, array $orderBy = null)
 * @method JobEvent[]    findAll()
 * @method JobEvent[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class JobEventRepository extends ServiceEntityRepository
{
    public const TYPE_JOB_EVENT_ESABORA = 'esabora';

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, JobEvent::class);
    }

    /**
     * @throws Exception
     */
    public function findLastJobEventByType(string $type = self::TYPE_JOB_EVENT_ESABORA): array
    {
        $connexion = $this->getEntityManager()->getConnection();
        $sql = 'SELECT MAX(j.created_at) AS last_event, p.id, p.nom, s.reference, j.status, j.title
                FROM job_event j
                INNER JOIN signalement s ON s.id = j.signalement_id
                INNER JOIN partner p ON p.id = j.partner_id
                WHERE type LIKE :type
                GROUP BY p.id, p.nom, s.reference,j.title, j.status
                ORDER BY p.id ASC, last_event DESC;';

        $statement = $connexion->prepare($sql);

        return $statement->executeQuery(['type' => $type])->fetchAllAssociative();
    }
}
