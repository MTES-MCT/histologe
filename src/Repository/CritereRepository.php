<?php

namespace App\Repository;

use App\Entity\Critere;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Critere|null find($id, $lockMode = null, $lockVersion = null)
 * @method Critere|null findOneBy(array $criteria, array $orderBy = null)
 * @method Critere[]    findAll()
 * @method Critere[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CritereRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Critere::class);
    }

    /**
     * @throws NonUniqueResultException
     * @throws NoResultException
     */
    public function getMaxScore()
    {
        return $this->createQueryBuilder('c')
            ->select('SUM(c.coef)')
            ->where('c.isArchive != 1')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @throws NonUniqueResultException
     * @throws NoResultException
     */
    public function getMaxNewScore(int $type): ?float
    {
        $connexion = $this->getEntityManager()->getConnection();

        $sql = 'SELECT SUM(new_coef * new_max_score) '.
        'FROM `critere` c '.
        'INNER JOIN (SELECT critere_id, MAX(new_score) AS new_max_score FROM criticite GROUP BY critere_id ) AS max_criticite '.
        'ON max_criticite.critere_id = c.id '.
        'WHERE c.is_archive = 0 AND c.type = '.$type;

        $statement = $connexion->prepare($sql);

        return $statement->executeQuery()->fetchOne();
    }

    public function findAllList()
    {
        return $this->createQueryBuilder('c')
            ->where('c.isArchive != 1')
            ->indexBy('c', 'c.id')
            ->getQuery()
            ->getResult();
    }

    /**
     * @throws NonUniqueResultException
     */
    public function findByLabel(string $label): ?Critere
    {
        return $this->createQueryBuilder('c')
            ->where('c.label LIKE :label')
            ->setParameter('label', "%{$label}%")
            ->andWhere('c.isArchive = 0')
            ->getQuery()
            ->getOneOrNullResult();
    }
}
