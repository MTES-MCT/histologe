<?php

namespace App\Repository;

use App\Entity\Enum\SignalementStatus;
use App\Entity\Notification;
use App\Entity\Signalement;
use App\Entity\Suivi;
use App\Entity\Territory;
use App\Entity\User;
use App\Repository\Behaviour\EntityCleanerRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Notification|null find($id, $lockMode = null, $lockVersion = null)
 * @method Notification|null findOneBy(array $criteria, array $orderBy = null)
 * @method Notification[]    findAll()
 * @method Notification[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class NotificationRepository extends ServiceEntityRepository implements EntityCleanerRepositoryInterface
{
    public function __construct(ManagerRegistry $registry, private array $params)
    {
        parent::__construct($registry, Notification::class);
    }

    public function getNotificationUserQueryBuilder(User $user, array $options): QueryBuilder
    {
        $qb = $this->createQueryBuilder('n')
            ->orderBy('suivi.createdAt', 'DESC')
            ->where('n.user = :user')
            ->andWhere('n.type = :type_notification')
            ->setParameter('user', $user)
            ->setParameter('type_notification', Notification::TYPE_SUIVI)
            ->leftJoin('n.user', 'user')
            ->leftJoin('n.suivi', 'suivi')
            ->leftJoin('suivi.createdBy', 'createdBy')
            ->leftJoin('n.signalement', 'signalement')
            ->leftJoin('n.affectation', 'affectation')
            ->addSelect('suivi', 'signalement', 'affectation', 'user', 'createdBy');

        $zip = $user->getTerritory()?->getZip();
        if ($user->isTerritoryAdmin() && isset($this->params[$zip])) {
            $partnerName = $this->params[$zip][$user->getPartner()?->getNom()] ?? null;
            if (null !== $partnerName) {
                $qb->andWhere('signalement.inseeOccupant IN (:authorized_codes_insee)')
                    ->setParameter('authorized_codes_insee', $options[$zip][$partnerName]);
            }
        }

        return $qb;
    }

    public function getNotificationUser(User $user, int $page, array $options): Paginator
    {
        $maxResult = Notification::MAX_LIST_PAGINATION;
        $firstResult = ($page - 1) * $maxResult;

        $queryBuilder = $this->getNotificationUserQueryBuilder($user, $options);
        $queryBuilder->setFirstResult($firstResult)->setMaxResults($maxResult);

        return new Paginator($queryBuilder->getQuery(), true);
    }

    /**
     * @throws \Exception
     */
    public function cleanOlderThan(string $period = Notification::EXPIRATION_PERIOD): int
    {
        return $this->createQueryBuilder('n')
            ->delete()
            ->andWhere('DATE(n.createdAt) <= :created_at')
            ->setParameter('created_at', (new \DateTimeImmutable($period))->format('Y-m-d'))
            ->getQuery()
            ->execute();
    }

    /**
     * @throws NonUniqueResultException
     * @throws NoResultException
     */
    public function countSignalementNewSuivi(User $user, ?Territory $territory): int
    {
        $qb = $this->createQueryBuilder('n')
            ->select('COUNT(DISTINCT n.signalement)')
            ->innerJoin('n.suivi', 'su')
            ->where('n.isSeen = :is_seen')
            ->andWhere('n.type = :type')
            ->andWhere('n.user = :user')
            ->andWhere('su.type IN (:type_suivi_usager, :type_suivi_partner)')
            ->setParameter('is_seen', 0)
            ->setParameter('type', 1)
            ->setParameter('user', $user)
            ->setParameter('type_suivi_usager', Suivi::TYPE_USAGER)
            ->setParameter('type_suivi_partner', Suivi::TYPE_PARTNER);

        if (null !== $territory) {
            $qb->innerJoin('n.signalement', 's')
                ->andWhere('s.territory = :territory')
                ->setParameter('territory', $territory);
        }

        return $qb->getQuery()->getSingleScalarResult();
    }

    public function findSignalementNewSuivi(User $user, ?Territory $territory): array
    {
        $qb = $this->createQueryBuilder('n')
            ->select('DISTINCT IDENTITY(n.signalement) as id')
            ->innerJoin('n.suivi', 'su')
            ->where('n.isSeen = :is_seen')
            ->andWhere('n.type = :type')
            ->andWhere('n.user = :user')
            ->andWhere('su.type IN (:type_suivi_usager, :type_suivi_partner)')
            ->setParameter('is_seen', 0)
            ->setParameter('type', 1)
            ->setParameter('user', $user)
            ->setParameter('type_suivi_usager', Suivi::TYPE_USAGER)
            ->setParameter('type_suivi_partner', Suivi::TYPE_PARTNER);

        if (null !== $territory) {
            $qb->innerJoin('n.signalement', 's')
                ->andWhere('s.territory = :territory')
                ->setParameter('territory', $territory);
        }

        return $qb->getQuery()->getResult(AbstractQuery::HYDRATE_SCALAR_COLUMN);
    }

    /**
     * @throws NonUniqueResultException
     * @throws NoResultException
     */
    public function countSignalementClosedNotSeen(?User $user, ?Territory $territory): int
    {
        $qb = $this->createQueryBuilder('n');
        $qb
            ->select('COUNT(DISTINCT n.signalement)')
            ->innerJoin('n.signalement', 'si')
            ->innerJoin('n.suivi', 'su')
            ->where('n.isSeen = :is_seen')
            ->andWhere('n.type = :type_notification')
            ->andWhere('n.user = :user')
            ->andWhere('si.statut = :statut')
            ->andWhere('su.description LIKE :description')
            ->setParameter('is_seen', 0)
            ->setParameter('type_notification', Notification::TYPE_SUIVI)
            ->setParameter('statut', Signalement::STATUS_CLOSED)
            ->setParameter('description', '%'.Suivi::DESCRIPTION_MOTIF_CLOTURE_ALL.'%')
            ->setParameter('user', $user);

        if (null !== $territory) {
            $qb->andWhere('si.territory = :territory')->setParameter('territory', $territory);
        }

        return $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * @throws NonUniqueResultException
     * @throws NoResultException
     */
    public function countAffectationClosedNotSeen(?User $user, ?Territory $territory): int
    {
        $qb = $this->createQueryBuilder('n');
        $qb
            ->select('COUNT(DISTINCT n.signalement)')
            ->innerJoin('n.signalement', 'si')
            ->innerJoin('n.suivi', 'su')
            ->where('n.isSeen = :is_seen')
            ->andWhere('n.type = :type_notification')
            ->andWhere('n.user = :user')
            ->andWhere('si.statut != :status_closed')
            ->andWhere('su.description NOT LIKE :description_all AND su.description LIKE :description_partner')
            ->setParameter('is_seen', 0)
            ->setParameter('type_notification', Notification::TYPE_SUIVI)
            ->setParameter('description_all', '%'.Suivi::DESCRIPTION_MOTIF_CLOTURE_ALL.'%')
            ->setParameter('description_partner', '%'.Suivi::DESCRIPTION_MOTIF_CLOTURE_PARTNER.'%')
            ->setParameter('status_closed', SignalementStatus::CLOSED)
            ->setParameter('user', $user);

        if (null !== $territory) {
            $qb->andWhere('si.territory = :territory')->setParameter('territory', $territory);
        }

        return $qb->getQuery()->getSingleScalarResult();
    }

    public function markUserNotificationsAsSeen(User $user, array $ids = []): void
    {
        $qb = $this->createQueryBuilder('n')
            ->update()
            ->set('n.isSeen', 1)
            ->where('n.user = :user')
            ->setParameter('user', $user);
        if (\count($ids)) {
            $qb->andWhere('n.id IN (:ids)')
                ->setParameter('ids', $ids);
        }

        $qb->getQuery()->execute();
    }

    public function deleteUserNotifications(User $user, array $ids = []): void
    {
        $qb = $this->createQueryBuilder('n')
            ->delete()
            ->where('n.user = :user')
            ->setParameter('user', $user);
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
}
