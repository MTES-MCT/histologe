<?php

namespace App\Repository;

use App\Entity\Affectation;
use App\Entity\UserSignalementSubscription;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<UserSignalementSubscription>
 */
class UserSignalementSubscriptionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserSignalementSubscription::class);
    }

    /**
     * @return array<UserSignalementSubscription>
     */
    public function findForAffectation(Affectation $affectation): array
    {
        $signalement = $affectation->getSignalement();
        $partner = $affectation->getPartner();

        $queryBuilder = $this->createQueryBuilder('s')
            ->innerJoin('s.user', 'u')
            ->innerJoin('u.userPartners', 'up')
            ->where('s.signalement = :signalement')->setParameter('signalement', $signalement)
            ->andWhere('up.partner = :partner')->setParameter('partner', $partner)
            ->andWhere('JSON_CONTAINS(u.roles, :role_admin_partner) = 1 OR JSON_CONTAINS(u.roles, :role_user_partner) = 1')
            ->setParameter('role_admin_partner', '"ROLE_ADMIN_PARTNER"')
            ->setParameter('role_user_partner', '"ROLE_USER_PARTNER"')
        ;

        return $queryBuilder->getQuery()->getResult();
    }
}
