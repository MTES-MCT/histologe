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

    public function findBailleursBy(string $name, string $zip): array
    {
        $terms = explode(' ', trim($name));
        $queryBuilder = $this
            ->createQueryBuilder('b')
            ->innerJoin('b.bailleurTerritories', 'bt')
            ->innerJoin('bt.territory', 't')
            ->where('t.zip = :zip')
            ->setParameter('zip', $zip);

        foreach ($terms as $index => $term) {
            $placeholder = 'term_'.$index;
            $queryBuilder
                ->andWhere($queryBuilder->expr()->like('b.name', ':'.$placeholder))
                ->setParameter($placeholder, '%'.$term.'%');
        }

        return $queryBuilder->getQuery()->getResult();
    }

    public function findOneBailleurBy(string $name, string $zip, bool $bailleurSanitized = false): ?Bailleur
    {
        $queryBuilder = $this
            ->createQueryBuilder('b')
            ->innerJoin('b.bailleurTerritories', 'bt')
            ->innerJoin('bt.territory', 't')
            ->where('t.zip = :zip')
            ->setParameter('zip', $zip);
        if ($bailleurSanitized) {
            $queryBuilder->andWhere('b.name = :name OR b.name = :sanitizedName')
            ->setParameter('name', $name)
            ->setParameter('sanitizedName', Bailleur::BAILLEUR_RADIE.' '.$name);
        } else {
            $queryBuilder->andWhere('b.name = :name')
            ->setParameter('name', $name);
        }

        return $queryBuilder->getQuery()->getOneOrNullResult();
    }
}
