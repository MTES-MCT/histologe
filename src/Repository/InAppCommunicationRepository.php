<?php

namespace App\Repository;

use App\Entity\InAppCommunication;
use App\Entity\User;
use App\Service\ListFilters\SearchInAppCommunication;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<InAppCommunication>
 */
class InAppCommunicationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, InAppCommunication::class);
    }

    /**
     * @return Paginator<InAppCommunication>
     */
    public function findFilteredPaginated(SearchInAppCommunication $searchInAppCommunication, int $maxResult): Paginator
    {
        $qb = $this->createQueryBuilder('i');
        $qb->leftJoin('i.inAppCommunicationUsers', 'iu')->addSelect('iu');

        if (!empty($searchInAppCommunication->getOrderType())) {
            [$orderField, $orderDirection] = explode('-', $searchInAppCommunication->getOrderType());
            $qb->orderBy($orderField, $orderDirection);
        } else {
            $qb->orderBy('i.id', 'ASC');
        }

        if ($searchInAppCommunication->getQueryTitleOrDescription()) {
            $qb->andWhere('LOWER(i.title) LIKE :queryTitleOrDescription OR LOWER(i.description) LIKE :queryTitleOrDescription');
            $qb->setParameter('queryTitleOrDescription', '%'.strtolower($searchInAppCommunication->getQueryTitleOrDescription()).'%');
        }

        if ($searchInAppCommunication->getCommunicationType()) {
            $qb->andWhere('i.communicationType = :communicationType');
            $qb->setParameter('communicationType', $searchInAppCommunication->getCommunicationType());
        }

        $firstResult = ($searchInAppCommunication->getPage() - 1) * $maxResult;
        $qb->setFirstResult($firstResult)->setMaxResults($maxResult);

        return new Paginator($qb->getQuery());
    }

    /**
     * @return list<InAppCommunication>
     */
    public function findForUser(User $user, ?int $id = null): array
    {
        $userRole = null;
        if ($user->isSuperAdmin() || $user->isTerritoryAdmin()) {
            $userRole = 'ROLE_ADMIN_TERRITORY';
        } elseif ($user->isPartnerAdmin()) {
            $userRole = 'ROLE_ADMIN_PARTNER';
        } elseif ($user->isUserPartner()) {
            $userRole = 'ROLE_USER_PARTNER';
        }

        if (!$userRole) {
            return [];
        }

        $qb = $this->createQueryBuilder('i')
            ->andWhere('JSON_LENGTH(i.userRoles) = 0 OR JSON_CONTAINS(i.userRoles, :userRole) = 1')
            ->setParameter('userRole', json_encode($userRole));

        if ($id) {
            $qb->andWhere('i.id = :id')->setParameter('id', $id);
        }

        return $qb->getQuery()->getResult();
    }
}
