<?php

namespace App\Repository;

use App\Entity\Affectation;
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

    public function deleteForAffectation(Affectation $affectation): void
    {
        $signalement = $affectation->getSignalement();
        $partner = $affectation->getPartner();

        $this->deleteForSignalementOrPartner(signalement: $signalement, partner: $partner);
    }

    public function deleteForSignalementOrPartner(?Signalement $signalement = null, ?Partner $partner = null): void
    {
        $queryBuilder = $this->createQueryBuilder('s')
            ->select('s')
            ->innerJoin('s.user', 'u')
            ->innerJoin('u.userPartners', 'up');
    
        if ($signalement) {
            $queryBuilder
                ->andWhere('s.signalement = :signalement')
                ->setParameter('signalement', $signalement);
        }
    
        if ($partner) {
            $queryBuilder
                ->andWhere('up.partner = :partner')
                ->setParameter('partner', $partner);
        }
    
        $subscriptions = $queryBuilder->getQuery()->getResult();
    
        foreach ($subscriptions as $subscription) {
            $this->_em->remove($subscription);
        }
    
        $this->_em->flush();
    }
}
