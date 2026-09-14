<?php

namespace App\Repository;

use App\Dto\NotificationSuiviUser;
use App\Entity\Enum\NotificationType;
use App\Entity\Notification;
use App\Entity\Signalement;
use App\Entity\User;
use App\Service\ListFilters\SearchNotification;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @extends ServiceEntityRepository<Notification>
 *
 * @method Notification|null find($id, $lockMode = null, $lockVersion = null)
 * @method Notification|null findOneBy(array<string, mixed> $criteria, array<string, mixed>|null $orderBy = null)
 * @method Notification[]    findAll()
 * @method Notification[]    findBy(array<string, mixed> $criteria, array<string, mixed>|null $orderBy = null, $limit = null, $offset = null)
 */
class NotificationRepository extends ServiceEntityRepository
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
            ->andWhere('n.type IN (:nouveau_suivi, :nouvelle_mention, :cloture_signalement, :nouvel_abonnement, :demande_abandon_procedure)')
            ->andWhere('n.deleted = :deleted')
            ->setParameter('user', $searchNotification->getUser())
            ->setParameter('nouveau_suivi', NotificationType::NOUVEAU_SUIVI)
            ->setParameter('nouvelle_mention', NotificationType::NOUVELLE_MENTION)
            ->setParameter('cloture_signalement', NotificationType::CLOTURE_SIGNALEMENT)
            ->setParameter('nouvel_abonnement', NotificationType::NOUVEL_ABONNEMENT)
            ->setParameter('demande_abandon_procedure', NotificationType::DEMANDE_ABANDON_PROCEDURE)
            ->setParameter('deleted', false)
            ->leftJoin('n.user', 'u')
            ->leftJoin('n.suivi', 's')
            ->leftJoin('s.createdBy', 'cb')
            ->leftJoin('n.signalement', 'si')
            ->leftJoin('si.address', 'address')
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
     * @return NotificationSuiviUser[]
     */
    public function getNotificationsFrom(Signalement $signalement): array
    {
        return array_map(
            static fn (array $row) => new NotificationSuiviUser(
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
