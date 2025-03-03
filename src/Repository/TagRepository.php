<?php

namespace App\Repository;

use App\Entity\Enum\SignalementStatus;
use App\Entity\Tag;
use App\Entity\Territory;
use App\Entity\User;
use App\Service\ListFilters\SearchTag;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Tag|null find($id, $lockMode = null, $lockVersion = null)
 * @method Tag|null findOneBy(array $criteria, array $orderBy = null)
 * @method Tag[]    findAll()
 * @method Tag[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TagRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Tag::class);
    }

    public function findAllActive(
        ?Territory $territory = null,
        ?User $user = null,
    ): mixed {
        $qb = $this->createQueryBuilder('t');
        $qb->andWhere('t.isArchive != 1')
            ->orderBy('t.label', 'ASC')
            ->indexBy('t', 't.id');
        if ($user && !$user->isSuperAdmin()) {
            $qb->andWhere('t.territory IN (:territories)')->setParameter('territories', $user->getPartnersTerritories());
        }
        if ($territory) {
            $qb->andWhere('t.territory = :territory')
                ->setParameter('territory', $territory);
        }

        return $qb->getQuery()
            ->getResult();
    }

    public function findFilteredPaginated(SearchTag $searchTag, int $maxResult): Paginator
    {
        $qb = $this->createQueryBuilder('t');
        $qb->select('t', 's')
            ->leftJoin('t.signalements', 's', 'WITH', 's.statut NOT IN (:signalement_status_list)')
            ->setParameter('signalement_status_list', [SignalementStatus::ARCHIVED, SignalementStatus::DRAFT, SignalementStatus::DRAFT_ARCHIVED])
            ->andWhere('t.isArchive != 1');

        if (!empty($searchTag->getOrderType())) {
            [$orderField, $orderDirection] = explode('-', $searchTag->getOrderType());
            $qb->orderBy($orderField, $orderDirection);
        } else {
            $qb->orderBy('t.label', 'ASC');
        }

        $qb->indexBy('t', 't.id');
        if ($searchTag->getTerritory()) {
            $qb->andWhere('t.territory = :territory')
                ->setParameter('territory', $searchTag->getTerritory());
        } elseif (!$searchTag->getUser()->isSuperAdmin()) {
            $qb->andWhere('t.territory IN (:territories)')
                ->setParameter('territories', $searchTag->getUser()->getPartnersTerritories());
        }
        if ($searchTag->getQueryTag()) {
            $qb->andWhere('t.label LIKE :search')
                ->setParameter('search', '%'.$searchTag->getQueryTag().'%');
        }

        $firstResult = ($searchTag->getPage() - 1) * $maxResult;
        $qb->setFirstResult($firstResult)->setMaxResults($maxResult);

        return new Paginator($qb->getQuery(), true);
    }
}
