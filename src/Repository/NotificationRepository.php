<?php

namespace App\Repository;

use App\Dto\NotificationSuiviUser;
use App\Entity\Enum\NotificationType;
use App\Entity\Enum\SignalementStatus;
use App\Entity\Notification;
use App\Entity\Signalement;
use App\Entity\Suivi;
use App\Entity\User;
use App\Repository\Behaviour\EntityCleanerRepositoryInterface;
use App\Service\ListFilters\SearchNotification;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @extends ServiceEntityRepository<Notification>
 *
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

    /**
     * @return Paginator<Notification>
     */
    public function findFilteredPaginated(SearchNotification $searchNotification, int $maxResult): Paginator
    {
        $qb = $this->createQueryBuilder('n')
            ->where('n.user = :user')
            ->andWhere('n.type IN (:nouveau_suivi, :cloture_signalement, :nouvel_abonnement)')
            ->andWhere('n.deleted = :deleted')
            ->setParameter('user', $searchNotification->getUser())
            ->setParameter('nouveau_suivi', NotificationType::NOUVEAU_SUIVI)
            ->setParameter('cloture_signalement', NotificationType::CLOTURE_SIGNALEMENT)
            ->setParameter('nouvel_abonnement', NotificationType::NOUVEL_ABONNEMENT)
            ->setParameter('deleted', false)
            ->leftJoin('n.user', 'u')
            ->leftJoin('n.suivi', 's')
            ->leftJoin('s.createdBy', 'cb')
            ->leftJoin('n.signalement', 'si')
            ->leftJoin('n.affectation', 'a')
            ->leftJoin('a.answeredBy', 'ab')
            ->addSelect('s', 'si', 'a', 'u', 'cb', 'ab');

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
                $qb->orderBy('CASE WHEN cb.nom IS NOT NULL THEN cb.nom WHEN ab.nom IS NOT NULL THEN ab.nom ELSE si.nomOccupant END', $orderDirection);
            } else {
                $qb->orderBy($orderField, $orderDirection);
            }
        } else {
            $qb->orderBy('n.createdAt', 'DESC');
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
