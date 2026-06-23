<?php

namespace App\Repository;

use App\Entity\SuiviDelayed;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Clock\ClockInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * @extends ServiceEntityRepository<SuiviDelayed>
 */
class SuiviDelayedRepository extends ServiceEntityRepository
{
    public function __construct(
        #[Autowire(env: 'SUIVI_DELAYED_DELAY_IN_MINUTES')]
        private readonly int $suiviDelayedDelayInMinutes,
        private readonly ClockInterface $clock,
        ManagerRegistry $registry,
    ) {
        parent::__construct($registry, SuiviDelayed::class);
    }

    /**
     * @return array<int, SuiviDelayed>
     */
    public function findSuiviDelayedWithExpiredDelay(): array
    {
        $limit = $this->clock->now()->modify('-'.$this->suiviDelayedDelayInMinutes.' minutes');

        return $this->createQueryBuilder('sd')
            ->where('EXISTS (
                SELECT 1 FROM '.SuiviDelayed::class.' sd2
                WHERE sd2.user = sd.user
                AND sd2.signalement = sd.signalement
                AND sd2.suiviCategory = sd.suiviCategory
                AND sd2.createdAt < :limit
            )')
            ->setParameter('limit', $limit)
            ->orderBy('sd.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
