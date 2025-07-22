<?php

namespace App\Repository;

use App\Entity\Affectation;
use App\Entity\Intervention;
use App\Entity\Partner;
use App\Entity\Signalement;
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
    public function findForAffectation(Affectation $affectation, bool $excludeRT = false): array
    {
        $signalement = $affectation->getSignalement();
        $partner = $affectation->getPartner();

        return $this->findForSignalementAndPartner($signalement, $partner, $excludeRT);
    }

    /**
     * @return array<UserSignalementSubscription>
     */
    public function findForIntervention(Intervention $intervention, bool $excludeRT = false): array
    {
        $signalement = $intervention->getSignalement();
        $partner = $intervention->getPartner();

        return $this->findForSignalementAndPartner($signalement, $partner, $excludeRT);
    }

    /**
     * @return array<UserSignalementSubscription>
     */
    public function findForSignalementAndPartner(Signalement $signalement, Partner $partner, bool $excludeRT): array
    {
        $queryBuilder = $this->createQueryBuilder('s')
            ->select('s', 'u')
            ->innerJoin('s.user', 'u')
            ->innerJoin('u.userPartners', 'up')
            ->where('s.signalement = :signalement')->setParameter('signalement', $signalement)
            ->andWhere('up.partner = :partner')->setParameter('partner', $partner);
        if ($excludeRT) {
            $queryBuilder->andWhere('JSON_CONTAINS(u.roles, :role_admin_territory) = 0')
            ->setParameter('role_admin_territory', 'ROLE_ADMIN_TERRITORY');
        }

        return $queryBuilder->getQuery()->getResult();
    }
}
