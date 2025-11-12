<?php

namespace App\Repository;

use App\Entity\Affectation;
use App\Entity\Enum\AffectationStatus;
use App\Entity\Intervention;
use App\Entity\Partner;
use App\Entity\Signalement;
use App\Entity\Suivi;
use App\Entity\User;
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
    public function findForIntervention(Intervention $intervention): array
    {
        $signalement = $intervention->getSignalement();
        $partner = $intervention->getPartner();

        return $this->findForSignalementAndPartner($signalement, $partner);
    }

    /**
     * @return array<UserSignalementSubscription>
     */
    public function findForSignalementAndPartner(Signalement $signalement, Partner $partner, bool $excludeRT = false): array
    {
        $queryBuilder = $this->createQueryBuilder('s')
            ->select('s', 'u')
            ->innerJoin('s.user', 'u')
            ->innerJoin('u.userPartners', 'up')
            ->where('s.signalement = :signalement')->setParameter('signalement', $signalement)
            ->andWhere('up.partner = :partner')->setParameter('partner', $partner);
        if ($excludeRT) {
            $queryBuilder->andWhere('JSON_CONTAINS(u.roles, :role_admin_territory) = 0')
            ->setParameter('role_admin_territory', '"ROLE_ADMIN_TERRITORY"');
        }

        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * @return array<UserSignalementSubscription>
     */
    public function findLegacyForUserInactiveOnSignalement(User $user): array
    {
        $acceptedAffectationSubQuery = $this->_em->createQueryBuilder()
            ->select('1')
            ->from(Affectation::class, 'a')
            ->where('a.signalement = s.signalement')
            ->andWhere('a.answeredBy = s.user')
            ->andWhere('a.statut = :statut');

        $queryBuilder = $this->createQueryBuilder('s');
        $queryBuilder->leftJoin(Suivi::class, 'suivi', 'WITH', 'suivi.signalement = s.signalement AND suivi.createdBy = s.user')
            ->where('s.user = :user')
            ->andWhere('suivi.id IS NULL')
            ->andWhere('s.isLegacy = true')
            ->andWhere($queryBuilder->expr()->not($queryBuilder->expr()->exists($acceptedAffectationSubQuery->getDQL())))
            ->setParameter('user', $user)
            ->setParameter('statut', AffectationStatus::ACCEPTED);

        return $queryBuilder->getQuery()->getResult();
    }
}
