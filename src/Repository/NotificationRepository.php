<?php

namespace App\Repository;

use App\Dto\NotificationSuiviUser;
use App\Entity\Enum\NotificationType;
use App\Entity\Enum\SignalementStatus;
use App\Entity\Notification;
use App\Entity\Signalement;
use App\Entity\Suivi;
use App\Entity\Territory;
use App\Entity\User;
use App\Repository\Behaviour\EntityCleanerRepositoryInterface;
use App\Service\ListFilters\SearchNotification;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @method Notification|null find($id, $lockMode = null, $lockVersion = null)
 * @method Notification|null findOneBy(array $criteria, array $orderBy = null)
 * @method Notification[]    findAll()
 * @method Notification[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class NotificationRepository extends ServiceEntityRepository implements EntityCleanerRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Notification::class);
    }

    public function findFilteredPaginated(SearchNotification $searchNotification, int $maxResult): Paginator
    {
        $qb = $this->createQueryBuilder('n')
            ->where('n.user = :user')
            ->andWhere('n.type IN (:nouveau_suivi, :cloture_signalement)')
            ->andWhere('n.deleted = :deleted')
            ->setParameter('user', $searchNotification->getUser())
            ->setParameter('nouveau_suivi', NotificationType::NOUVEAU_SUIVI)
            ->setParameter('cloture_signalement', NotificationType::CLOTURE_SIGNALEMENT)
            ->setParameter('deleted', false)
            ->leftJoin('n.user', 'u')
            ->leftJoin('n.suivi', 's')
            ->leftJoin('s.createdBy', 'cb')
            ->leftJoin('n.signalement', 'si')
            ->leftJoin('n.affectation', 'a')
            ->addSelect('s', 'si', 'a', 'u', 'cb');

        if (!empty($searchNotification->getOrderType())) {
            [$orderField, $orderDirection] = explode('-', $searchNotification->getOrderType());
            if ('si.reference' === $orderField) {
                $qb->orderBy(
                    'SUBSTRING_INDEX(si.reference, \'-\', 1)',
                    $orderDirection
                )
                ->addOrderBy(
                    'CAST(SUBSTRING_INDEX(si.reference, \'-\', -1) AS UNSIGNED)',
                    $orderDirection
                );
            } elseif ('cb.nom' === $orderField) {
                $qb->orderBy('CASE WHEN cb.nom IS NOT NULL THEN cb.nom ELSE si.nomOccupant END', $orderDirection);
            } else {
                $qb->orderBy($orderField, $orderDirection);
            }
        } else {
            $qb->orderBy('s.createdAt', 'DESC');
        }

        $firstResult = ($searchNotification->getPage() - 1) * $maxResult;
        $qb->setFirstResult($firstResult)->setMaxResults($maxResult);

        return new Paginator($qb->getQuery());
    }

    /**
     * @throws \Exception
     */
    public function cleanOlderThan(string $period = Notification::EXPIRATION_PERIOD): int
    {
        return $this->createQueryBuilder('n')
            ->delete()
            ->andWhere('DATE(n.createdAt) <= :created_at')
            ->andWhere('n.type NOT LIKE :notification_type')
            ->setParameter('created_at', (new \DateTimeImmutable($period))->format('Y-m-d'))
            ->setParameter('notification_type', NotificationType::SUIVI_USAGER)
            ->getQuery()
            ->execute();
    }

    /**
     * @param array<int, int> $territories
     *
     * @throws NonUniqueResultException
     * @throws NoResultException
     */
    public function countSignalementNewSuivi(User $user, array $territories): int
    {
        $qb = $this->createQueryBuilder('n')
            ->select('COUNT(DISTINCT n.signalement)')
            ->innerJoin('n.suivi', 'su')
            ->where('n.isSeen = :is_seen')
            ->andWhere('n.type IN (:nouveau_suivi, :cloture_signalement)')
            ->andWhere('n.deleted = :deleted')
            ->andWhere('n.user = :user')
            ->andWhere('su.type IN (:type_suivi_usager, :type_suivi_partner)')
            ->setParameter('is_seen', 0)
            ->setParameter('nouveau_suivi', NotificationType::NOUVEAU_SUIVI)
            ->setParameter('cloture_signalement', NotificationType::CLOTURE_SIGNALEMENT)
            ->setParameter('deleted', false)
            ->setParameter('user', $user)
            ->setParameter('type_suivi_usager', Suivi::TYPE_USAGER)
            ->setParameter('type_suivi_partner', Suivi::TYPE_PARTNER);

        if (\count($territories)) {
            $qb->innerJoin('n.signalement', 's')
                ->andWhere('s.territory IN (:territories)')
                ->setParameter('territories', $territories);
        }

        return $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function findSignalementNewSuivi(User $user, ?Territory $territory): array
    {
        $qb = $this->createQueryBuilder('n')
            ->select('DISTINCT IDENTITY(n.signalement) as id')
            ->innerJoin('n.suivi', 'su')
            ->where('n.isSeen = :is_seen')
            ->andWhere('n.type IN (:nouveau_suivi, :cloture_signalement)')
            ->andWhere('n.deleted = :deleted')
            ->andWhere('n.user = :user')
            ->andWhere('su.type IN (:type_suivi_usager, :type_suivi_partner)')
            ->setParameter('is_seen', 0)
            ->setParameter('nouveau_suivi', NotificationType::NOUVEAU_SUIVI)
            ->setParameter('cloture_signalement', NotificationType::CLOTURE_SIGNALEMENT)
            ->setParameter('deleted', false)
            ->setParameter('user', $user)
            ->setParameter('type_suivi_usager', Suivi::TYPE_USAGER)
            ->setParameter('type_suivi_partner', Suivi::TYPE_PARTNER);

        if (null !== $territory) {
            $qb->innerJoin('n.signalement', 's')
                ->andWhere('s.territory = :territory')
                ->setParameter('territory', $territory);
        } elseif (!$user->isSuperAdmin()) {
            $qb->innerJoin('n.signalement', 's')
            ->andWhere('s.territory IN (:territories)')
            ->setParameter('territories', $user->getPartnersTerritories());
        }

        return $qb->getQuery()->getResult(AbstractQuery::HYDRATE_SCALAR_COLUMN);
    }

    /**
     * @param array<int, int> $territories
     *
     * @throws NonUniqueResultException
     * @throws NoResultException
     */
    public function countSignalementClosedNotSeen(?User $user, array $territories): int
    {
        $qb = $this->createQueryBuilder('n');
        $qb
            ->select('COUNT(DISTINCT n.signalement)')
            ->innerJoin('n.signalement', 'si')
            ->innerJoin('n.suivi', 'su')
            ->where('n.isSeen = :is_seen')
            ->andWhere('n.type IN (:nouveau_suivi, :cloture_signalement)')
            ->andWhere('n.deleted = :deleted')
            ->andWhere('n.user = :user')
            ->andWhere('si.statut = :statut')
            ->andWhere('su.description LIKE :description')
            ->setParameter('is_seen', 0)
            ->setParameter('nouveau_suivi', NotificationType::NOUVEAU_SUIVI)
            ->setParameter('cloture_signalement', NotificationType::CLOTURE_SIGNALEMENT)
            ->setParameter('deleted', false)
            ->setParameter('statut', SignalementStatus::CLOSED->value)
            ->setParameter('description', Suivi::DESCRIPTION_MOTIF_CLOTURE_ALL.'%')
            ->setParameter('user', $user);

        if (\count($territories)) {
            $qb->andWhere('si.territory IN (:territories)')->setParameter('territories', $territories);
        }

        return $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * @param array<int, int> $territories
     *
     * @throws NonUniqueResultException
     * @throws NoResultException
     */
    public function countAffectationClosedNotSeen(?User $user, array $territories): int
    {
        $qb = $this->createQueryBuilder('n');
        $qb
            ->select('COUNT(DISTINCT n.signalement)')
            ->innerJoin('n.signalement', 'si')
            ->innerJoin('n.suivi', 'su')
            ->where('n.isSeen = :is_seen')
            ->andWhere('n.type IN (:nouveau_suivi, :cloture_signalement)')
            ->andWhere('n.deleted = :deleted')
            ->andWhere('n.user = :user')
            ->andWhere('si.statut != :status_closed')
            ->andWhere('su.description NOT LIKE :description_all AND su.description LIKE :description_partner')
            ->setParameter('is_seen', 0)
            ->setParameter('nouveau_suivi', NotificationType::NOUVEAU_SUIVI)
            ->setParameter('cloture_signalement', NotificationType::CLOTURE_SIGNALEMENT)
            ->setParameter('deleted', false)
            ->setParameter('description_all', Suivi::DESCRIPTION_MOTIF_CLOTURE_ALL.'%')
            ->setParameter('description_partner', Suivi::DESCRIPTION_MOTIF_CLOTURE_PARTNER.'%')
            ->setParameter('status_closed', SignalementStatus::CLOSED)
            ->setParameter('user', $user);

        if (\count($territories)) {
            $qb->andWhere('si.territory IN (:territories)')->setParameter('territories', $territories);
        }

        return $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * @param array<int, int|string> $ids
     */
    public function markUserNotificationsAsSeen(User $user, array $ids = []): void
    {
        $qb = $this->createQueryBuilder('n')
            ->update()
            ->set('n.isSeen', 1)
            ->where('n.user = :user')
            ->setParameter('user', $user)
            ->andWhere('n.type IN (:nouveau_suivi, :cloture_signalement)')
            ->setParameter('nouveau_suivi', NotificationType::NOUVEAU_SUIVI)
            ->setParameter('cloture_signalement', NotificationType::CLOTURE_SIGNALEMENT)
            ->andWhere('n.deleted = :deleted')
            ->setParameter('deleted', false);
        if (\count($ids)) {
            $qb->andWhere('n.id IN (:ids)')
                ->setParameter('ids', $ids);
        }

        $qb->getQuery()->execute();
    }

    /**
     * @param array<int, int|string> $ids
     */
    public function deleteUserNotifications(User $user, array $ids = []): void
    {
        $qb = $this->createQueryBuilder('n')
            ->update()
            ->set('n.deleted', 1)
            ->where('n.user = :user')
            ->setParameter('user', $user)
            ->andWhere('n.deleted = :deleted')
            ->setParameter('deleted', false)
            ->andWhere('n.type IN (:nouveau_suivi, :cloture_signalement)')
            ->setParameter('nouveau_suivi', NotificationType::NOUVEAU_SUIVI)
            ->setParameter('cloture_signalement', NotificationType::CLOTURE_SIGNALEMENT);
        if (\count($ids)) {
            $qb->andWhere('n.id IN (:ids)')
                ->setParameter('ids', $ids);
        }

        $qb->getQuery()->execute();
    }

    public function deleteBySignalement(Signalement $signalement): void
    {
        $qb = $this->createQueryBuilder('n')
            ->delete()
            ->where('n.signalement = :signalement')
            ->setParameter('signalement', $signalement);

        $qb->getQuery()->execute();
    }

    /**
     * @param array<int, int> $suiviIds
     */
    public function deleteBySuiviIds(array $suiviIds): void
    {
        $qb = $this->createQueryBuilder('n')
            ->delete()
            ->where('n.suivi in (:suivis)')
            ->setParameter('suivis', $suiviIds);

        $qb->getQuery()->execute();
    }

    /**
     * @return array<int, Notification>
     */
    public function findWaitingSummaryForUser(User $user): array
    {
        return $this->createQueryBuilder('n')
            ->select('n', 's')
            ->leftJoin('n.signalement', 's')
            ->where('n.user = :user')
            ->andWhere('n.waitMailingSummary = :waitMailingSummary')
            ->setParameter('user', $user)
            ->setParameter('waitMailingSummary', true)
            ->addOrderBy('n.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @param array<int, Notification> $notifications
     * @param array<string, mixed>     $data
     */
    public function massUpdate(array $notifications, array $data): void
    {
        $qb = $this->createQueryBuilder('n')
        ->update()
        ->set('n.waitMailingSummary', ':waitMailingSummary')
        ->setParameter('waitMailingSummary', $data['waitMailingSummary'])
        ->where('n.id IN (:ids)')
        ->setParameter('ids', array_map(fn (Notification $notification) => $notification->getId(), $notifications));
        if (isset($data['mailingSummarySentAt'])) {
            $qb->set('n.mailingSummarySentAt', ':mailingSummarySentAt')
                ->setParameter('mailingSummarySentAt', $data['mailingSummarySentAt']);
        }

        $qb->getQuery()->execute();
    }

    /**
     * @return NotificationSuiviUser[]
     */
    public function getNotificationsFrom(Signalement $signalement): array
    {
        return array_map(
            fn (array $row) => new NotificationSuiviUser(
                (int) $row['suiviId'],
                (int) $row['userId'],
                (bool) $row['isSeen'],
                $row['suiviCreatedAt']
            ),
            $this->createQueryBuilder('n')
                ->select('s.id as suiviId', 'u.id as userId', 'n.isSeen as isSeen', 's.createdAt as suiviCreatedAt')
                ->join('n.user', 'u')
                ->join('n.suivi', 's')
                ->andWhere('n.signalement = :signalement')
                ->andWhere('u.id IN (:usager_ids)')
                ->setParameter('signalement', $signalement)
                ->setParameter('usager_ids', $signalement->getUsagerIds())
                ->orderBy('s.createdAt', 'DESC')
                ->getQuery()
                ->getArrayResult()
        );
    }

    /**
     * @param NotificationType[] $includedNotificationTypes
     *
     * @return Notification[]
     */
    public function findUnseenNotificationsBy(
        Signalement $signalement,
        UserInterface $user,
        array $includedNotificationTypes = [],
    ): array {
        $qb = $this->createQueryBuilder('n')
            ->where('n.signalement = :signalement')
            ->andWhere('n.user = :user')
            ->andWhere('n.isSeen = :isSeen')
            ->setParameter('signalement', $signalement)
            ->setParameter('user', $user)
            ->setParameter('isSeen', false);

        if (!empty($includedNotificationTypes)) {
            $qb->andWhere('n.type IN (:includedTypes)')
                ->setParameter('includedTypes', $includedNotificationTypes);
        }

        return $qb->getQuery()->getResult();
    }
}
