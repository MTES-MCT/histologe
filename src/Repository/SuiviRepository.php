<?php

namespace App\Repository;

use App\Entity\Enum\SuiviCategory;
use App\Entity\Signalement;
use App\Entity\Suivi;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Clock\ClockInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * @extends ServiceEntityRepository<Suivi>
 *
 * @method Suivi|null find($id, $lockMode = null, $lockVersion = null)
 * @method Suivi|null findOneBy(array<string, mixed> $criteria, array<string, mixed>|null $orderBy = null)
 * @method Suivi[]    findAll()
 * @method Suivi[]    findBy(array<string, mixed> $criteria, array<string, mixed>|null $orderBy = null, $limit = null, $offset = null)
 */
class SuiviRepository extends ServiceEntityRepository
{
    public function __construct(
        ManagerRegistry $registry,
        #[Autowire(env: 'DELAY_SUIVI_EDITABLE_IN_MINUTES')]
        private readonly int $delaySuiviEditableInMinutes,
        private ClockInterface $clock,
    ) {
        parent::__construct($registry, Suivi::class);
    }

    /**
     * @return array<int, Suivi>
     */
    public function findSuiviByDescription(
        Signalement $signalement,
        string $description,
        ?SuiviCategory $suiviCategory = null,
    ): array {
        $qb = $this->createQueryBuilder('s');
        $qb->where('s.signalement = :signalement')
            ->andWhere('s.description LIKE :description')
            ->setParameter('signalement', $signalement)
            ->setParameter('description', '%'.$description.'%');

        if (null !== $suiviCategory) {
            $qb
                ->andWhere('s.category = :category')
                ->setParameter('category', $suiviCategory);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * @return array<int, Suivi>
     *
     * @throws NonUniqueResultException
     */
    public function findAllSuiviBy(Signalement $signalement, int $typeSuivi): array
    {
        $qb = $this->createQueryBuilder('s');
        $qb->where('s.signalement = :signalement')
            ->andWhere('s.type = :type')
            ->orderBy('s.createdAt', 'ASC')
            ->setParameter('signalement', $signalement)
            ->setParameter('type', $typeSuivi);

        return $qb->getQuery()->getResult();
    }

    /**
     * @return array<string, Suivi>
     */
    public function findExistingEventsForSCHS(): array
    {
        $qb = $this->createQueryBuilder('s')
            ->where('s.originalData IS NOT NULL')
            ->andWhere('s.category = :category')
            ->setParameter('category', SuiviCategory::MESSAGE_ESABORA_SCHS);

        $list = $qb->getQuery()->getResult();
        $indexed = [];
        foreach ($list as $suivi) {
            /* @var Suivi $suivi */
            $indexed[$suivi->getOriginalData()['keyDataList'][1]] = $suivi;
        }

        return $indexed;
    }

    /**
     * @throws NonUniqueResultException
     */
    public function findLastPublicSuivi(Signalement $signalement): ?Suivi
    {
        $qb = $this->createQueryBuilder('s');
        $qb->where('s.signalement = :signalement')
            ->andWhere('s.isVisibleForUsager = 1')
            ->andWhere('s.deletedBy IS NULL')
            ->setParameter('signalement', $signalement)
            ->andWhere('s.category NOT IN (:excludedCategories)')// ignore suivi usager
            ->setParameter('excludedCategories', [SuiviCategory::MESSAGE_USAGER, SuiviCategory::MESSAGE_USAGER_POST_CLOTURE, SuiviCategory::SIGNALEMENT_EDITED_FO]);

        $qb->orderBy('s.createdAt', 'DESC')->setMaxResults(1);

        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * @return array<int, Suivi>
     */
    public function findWithWaitingNotificationAndExpiredDelay(): array
    {
        $limit = $this->clock->now()->modify('-'.$this->delaySuiviEditableInMinutes.' minutes');
        $qb = $this->createQueryBuilder('s');
        $qb->where('s.createdAt < :limit')
            ->setParameter('limit', $limit)
            ->andWhere('s.waitingNotification = 1');

        return $qb->getQuery()->getResult();
    }
}
