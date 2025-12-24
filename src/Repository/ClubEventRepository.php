<?php

namespace App\Repository;

use App\Entity\ClubEvent;
use App\Service\ListFilters\SearchClubEvent;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Clock\ClockInterface;

/**
 * @extends ServiceEntityRepository<ClubEvent>
 */
class ClubEventRepository extends ServiceEntityRepository
{
    public function __construct(
        ManagerRegistry $registry,
        private readonly ClockInterface $clock,
    ) {
        parent::__construct($registry, ClubEvent::class);
    }

    /**
     * @return array<ClubEvent>
     */
    public function findInFuture(): array
    {
        $qb = $this->createQueryBuilder('c')
            ->andWhere('c.dateEvent >= :now')
            ->setParameter('now', $this->clock->now()->setTime(0, 0, 0))
            ->orderBy('c.dateEvent', 'ASC');

        return $qb->getQuery()->getResult();
    }

    /**
     * @return Paginator<ClubEvent>
     */
    public function findFilteredPaginated(SearchClubEvent $searchClubEvent, int $maxResult): Paginator
    {
        $qb = $this->createQueryBuilder('c');

        if (!empty($searchClubEvent->getOrderType())) {
            [$orderField, $orderDirection] = explode('-', $searchClubEvent->getOrderType());
            $qb->orderBy($orderField, $orderDirection);
        } else {
            $qb->orderBy('c.dateEvent', 'ASC');
        }

        if (true === $searchClubEvent->getIsInFuture()) {
            $qb->andWhere('c.dateEvent >= :now')
               ->setParameter('now', (new \DateTimeImmutable('today'))->setTime(0, 0, 0));
        } elseif (false === $searchClubEvent->getIsInFuture()) {
            $qb->andWhere('c.dateEvent < :now')
               ->setParameter('now', (new \DateTimeImmutable('today'))->setTime(0, 0, 0));
        }

        if ($searchClubEvent->getQueryName()) {
            $qb->andWhere('LOWER(c.name) LIKE :queryName');
            $qb->setParameter('queryName', '%'.strtolower($searchClubEvent->getQueryName()).'%');
        }

        if ($searchClubEvent->getPartnerType()) {
            $qb->andWhere('JSON_CONTAINS(c.partnerTypes, :partnerType) = 1');
            $qb->setParameter('partnerType', json_encode($searchClubEvent->getPartnerType()));
        }

        if ($searchClubEvent->getPartnerCompetence()) {
            $qb->andWhere('JSON_CONTAINS(c.partnerCompetences, :partnerCompetence) = 1');
            $qb->setParameter('partnerCompetence', json_encode($searchClubEvent->getPartnerCompetence()));
        }

        $firstResult = ($searchClubEvent->getPage() - 1) * $maxResult;
        $qb->setFirstResult($firstResult)->setMaxResults($maxResult);

        return new Paginator($qb->getQuery());
    }
}
