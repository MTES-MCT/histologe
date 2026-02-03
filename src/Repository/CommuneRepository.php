<?php

namespace App\Repository;

use App\Entity\Commune;
use App\Entity\Territory;
use App\Entity\User;
use App\Service\ListFilters\SearchCommune;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Commune>
 */
class CommuneRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Commune::class);
    }

    public function add(Commune $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Commune $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function findEpciByCommuneTerritory(?Territory $territory = null, ?User $user = null): array
    {
        $qb = $this->createQueryBuilder('c')
            ->select('distinct e.code, e.nom')
            ->join('c.epci', 'e');
        if ($user && !$user->isSuperAdmin()) {
            $qb->andWhere('c.territory IN (:territories)')->setParameter('territories', $user->getPartnersTerritories());
        }
        if (null !== $territory) {
            $qb
                ->andWhere('c.territory = :territory')
                ->setParameter('territory', $territory);
        }

        return $qb->getQuery()->getResult();
    }

    public function findDistinctCommuneCodesInseeForCodeInseeList(array $codesInsee): array
    {
        return $this->createQueryBuilder('c')
            ->where('c.codeInsee IN (:codesInsee)')
            ->andWhere('c.id IN (
                SELECT MIN(c2.id) FROM '.Commune::class.' c2 
                WHERE c2.codeInsee IN (:codesInsee) 
                GROUP BY c2.codeInsee
            )')
            ->setParameter('codesInsee', $codesInsee)
            ->orderBy('c.nom', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Paginator<Commune>
     */
    public function findFilteredPaginated(SearchCommune $searchCommune, int $maxResult): Paginator
    {
        $qb = $this->createQueryBuilder('c');
        $qb->select('c', 't', 'e');
        $qb->leftJoin('c.territory', 't');
        $qb->leftJoin('c.epci', 'e');

        if (!empty($searchCommune->getOrderType())) {
            [$orderField, $orderDirection] = explode('-', $searchCommune->getOrderType());
            $qb->orderBy($orderField, $orderDirection);
        } else {
            $qb->orderBy('c.nom', 'ASC');
        }

        if ($searchCommune->getQueryName()) {
            $qb->andWhere('LOWER(c.nom) LIKE :queryName');
            $qb->setParameter('queryName', '%'.strtolower($searchCommune->getQueryName()).'%');
        }
        if (null !== $searchCommune->getTerritory()) {
            $qb->andWhere('c.territory = :territory')->setParameter('territory', $searchCommune->getTerritory());
        }
        if (null !== $searchCommune->getEpci()) {
            $qb->andWhere('c.epci = :epci')->setParameter('epci', $searchCommune->getEpci());
        }
        if ($searchCommune->getCodePostal()) {
            $qb->andWhere('c.codePostal = :codePostal');
            $qb->setParameter('codePostal', $searchCommune->getCodePostal());
        }
        if ($searchCommune->getCodeInsee()) {
            $qb->andWhere('c.codeInsee = :codeInsee');
            $qb->setParameter('codeInsee', $searchCommune->getCodeInsee());
        }

        $firstResult = ($searchCommune->getPage() - 1) * $maxResult;
        $qb->setFirstResult($firstResult)->setMaxResults($maxResult);

        return new Paginator($qb->getQuery());
    }
}
