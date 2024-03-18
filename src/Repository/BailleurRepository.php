<?php

namespace App\Repository;

use App\Entity\Bailleur;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Bailleur>
 *
 * @method Bailleur|null find($id, $lockMode = null, $lockVersion = null)
 * @method Bailleur|null findOneBy(array $criteria, array $orderBy = null)
 * @method Bailleur[]    findAll()
 * @method Bailleur[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class BailleurRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Bailleur::class);
    }

    public function findActiveBy(string $name, string $zip): array
    {
        $terms = explode(' ', trim($name));
        $queryBuilder = $this
            ->createQueryBuilder('b')
            ->innerJoin('b.territories', 't')
            ->where('t.zip = :zip')
            ->setParameter('zip', $zip)
            ->andWhere('b.active = true');

        foreach ($terms as $index => $term) {
            $placeholder = 'term_'.$index;
            $queryBuilder
                ->andWhere($queryBuilder->expr()->like('b.name', ':'.$placeholder))
                ->setParameter($placeholder, '%'.$term.'%');
        }

        return $queryBuilder->getQuery()->getResult();
    }

    public function findOneActiveBy(string $name, string $zip): ?Bailleur
    {
        $queryBuilder = $this
            ->createQueryBuilder('b')
            ->innerJoin('b.territories', 't')
            ->where('t.zip = :zip')
            ->setParameter('zip', $zip)
            ->andWhere('b.name LIKE :name')
            ->setParameter('name', $name)
            ->andWhere('b.active = true');

        return $queryBuilder->getQuery()->getOneOrNullResult();
    }
}
