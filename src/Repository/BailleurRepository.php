<?php

namespace App\Repository;

use App\Entity\Bailleur;
use App\Entity\Territory;
use App\Entity\User;
use App\Service\ListFilters\SearchBailleur;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator;
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

    public function findBailleursByTerritory(User $user, ?Territory $territory): array
    {
        $queryBuilder = $this
            ->createQueryBuilder('b');
        if ($territory || !$user->isSuperAdmin()) {
            $queryBuilder->innerJoin('b.bailleurTerritories', 'bt');
            if (!$user->isSuperAdmin()) {
                $queryBuilder->andWhere('bt.territory IN (:territories)')->setParameter('territories', $user->getPartnersTerritories());
            }
            if ($territory) {
                $queryBuilder->andWhere('bt.territory = :territory')->setParameter('territory', $territory);
            }
        }
        $queryBuilder->orderBy('b.name', 'ASC');

        return $queryBuilder->getQuery()->getResult();
    }

    public function getBailleursByTerritoryQueryBuilder(string $zip): QueryBuilder
    {
        $queryBuilder = $this
            ->createQueryBuilder('b');
        $queryBuilder->innerJoin('b.bailleurTerritories', 'bt')
        ->innerJoin('bt.territory', 't')
        ->where('t.zip = :zip')
        ->setParameter('zip', $zip);
        $queryBuilder->orderBy('b.name', 'ASC');

        return $queryBuilder;
    }

    public function findBailleursBy(string $name, Territory $territory): array
    {
        $terms = explode(' ', mb_trim($name));
        $queryBuilder = $this
            ->createQueryBuilder('b')
            ->innerJoin('b.bailleurTerritories', 'bt')
            ->innerJoin('bt.territory', 't')
            ->where('t.id = :territory')
            ->setParameter('territory', $territory->getId());

        foreach ($terms as $index => $term) {
            $placeholder = 'term_'.$index;
            $queryBuilder
                ->andWhere('b.name LIKE :'.$placeholder.' OR b.raisonSociale LIKE :'.$placeholder)
                ->setParameter($placeholder, '%'.$term.'%');
        }

        return $queryBuilder->getQuery()->getResult();
    }

    public function findOneBailleurBy(string $name, Territory $territory, bool $bailleurSanitized = false): ?Bailleur
    {
        $queryBuilder = $this
            ->createQueryBuilder('b')
            ->innerJoin('b.bailleurTerritories', 'bt')
            ->innerJoin('bt.territory', 't')
            ->where('t.id = :territory')
            ->setParameter('territory', $territory->getId());
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

    public function findBailleursIndexedByName(?bool $raisonSociale = false): array
    {
        $list = $this->createQueryBuilder('b')
            ->leftJoin('b.bailleurTerritories', 'bt')
            ->addSelect('bt')
            ->getQuery()
            ->getResult();
        $indexed = [];
        foreach ($list as $bailleur) {
            if ($raisonSociale) {
                $name = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', mb_strtoupper($bailleur->getRaisonSociale()));
            } else {
                $name = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', mb_strtoupper($bailleur->getName()));
            }
            $indexed[$name] = $bailleur;
        }

        return $indexed;
    }

    public function findFilteredPaginated(SearchBailleur $searchBailleur, int $maxResult): Paginator
    {
        $qb = $this->createQueryBuilder('b');
        $qb->select('b', 'bt')
        ->leftJoin('b.bailleurTerritories', 'bt');

        if (!empty($searchBailleur->getOrderType())) {
            [$orderField, $orderDirection] = explode('-', $searchBailleur->getOrderType());
            $qb->orderBy($orderField, $orderDirection);
        } else {
            $qb->orderBy('b.name', 'ASC');
        }

        if ($searchBailleur->getQueryName()) {
            $qb->andWhere('LOWER(b.name) LIKE :queryName');
            $qb->setParameter('queryName', '%'.strtolower($searchBailleur->getQueryName()).'%');
        }
        if (null !== $searchBailleur->getTerritory()) {
            $qb->innerJoin('b.bailleurTerritories', 'bt_select')
                ->andWhere('bt_select.territory = :territory')
                ->setParameter('territory', $searchBailleur->getTerritory());
        }

        $firstResult = ($searchBailleur->getPage() - 1) * $maxResult;
        $qb->setFirstResult($firstResult)->setMaxResults($maxResult);

        return new Paginator($qb->getQuery());
    }
}
