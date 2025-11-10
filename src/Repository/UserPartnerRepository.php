<?php

namespace App\Repository;

use App\Entity\Enum\UserStatus;
use App\Entity\UserPartner;
use App\Service\ListFilters\SearchAnnuaireAgent;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<UserPartner>
 */
class UserPartnerRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserPartner::class);
    }

    public function findAnnuaireAgentPaginated(SearchAnnuaireAgent $search, int $maxResult): Paginator
    {
        $qb = $this->createAnnuaireAgentQueryBuilder($search);

        $firstResult = ($search->getPage() - 1) * $maxResult;
        $qb->setFirstResult($firstResult)->setMaxResults($maxResult);

        return new Paginator($qb->getQuery());
    }

    /**
     * @return array<int, UserPartner>
     */
    public function findAnnuaireAgent(SearchAnnuaireAgent $search): array
    {
        $qb = $this->createAnnuaireAgentQueryBuilder($search);

        return $qb->getQuery()->getResult();
    }

    public function createAnnuaireAgentQueryBuilder(SearchAnnuaireAgent $search): QueryBuilder
    {
        $qb = $this->createQueryBuilder('up')
            ->select('up', 'u', 'p', 't')
            ->leftJoin('up.user', 'u')
            ->leftJoin('up.partner', 'p')
            ->leftJoin('p.territory', 't')
            ->where('p.isArchive != 1')
            ->andWhere('u.statut = :statut')
            ->setParameter('statut', UserStatus::ACTIVE)
            ->addOrderBy('t.zip', 'ASC')
            ->addOrderBy('p.nom', 'ASC')
            ->addOrderBy('u.nom', 'ASC');

        if ($search->getTerritory()) {
            $qb->andWhere('p.territory = :territory')->setParameter('territory', $search->getTerritory());
        } elseif (!$search->getUser()->isSuperAdmin()) {
            $qb->andWhere('p.territory IN (:territories)')
                ->setParameter('territories', $search->getUser()->getPartnersTerritories());
        }
        if ($search->getQueryUser()) {
            $qb->andWhere('LOWER(u.nom) LIKE :queryUser OR LOWER(u.prenom) LIKE :queryUser OR LOWER(u.email) LIKE :queryUser');
            $qb->setParameter('queryUser', '%'.strtolower($search->getQueryUser()).'%');
        }
        if ($search->getPartners()->count() > 0) {
            $qb->andWhere('up.partner IN (:partners)')->setParameter('partners', $search->getPartners());
        }
        if (!empty($search->getOrderType())) {
            [$orderField, $orderDirection] = explode('-', $search->getOrderType());
            $qb->orderBy($orderField, $orderDirection);
        } else {
            $qb->orderBy('u.nom', 'ASC');
        }

        return $qb;
    }
}
