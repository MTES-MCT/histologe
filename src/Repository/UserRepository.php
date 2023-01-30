<?php

namespace App\Repository;

use App\Entity\Partner;
use App\Entity\Territory;
use App\Entity\User;
use App\Dto\CountUser;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;

/**
 * @method User|null find($id, $lockMode = null, $lockVersion = null)
 * @method User|null findOneBy(array $criteria, array $orderBy = null)
 * @method User[]    findAll()
 * @method User[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserRepository extends ServiceEntityRepository implements PasswordUpgraderInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    /**
     * Used to upgrade (rehash) the user's password automatically over time.
     */
    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', \get_class($user)));
        }

        $user->setPassword($newHashedPassword);
        $this->_em->persist($user);
        $this->_em->flush();
    }

    public function findAdmins()
    {
        return $this->createQueryBuilder('u')
            ->select('PARTIAL u.{id,email,isMailingActive}')
            ->where('u.roles LIKE :role')
            ->setParameter('role', '["ROLE_ADMIN"]')
            ->orWhere('u.roles LIKE :role2')
            ->setParameter('role2', '["ROLE_ADMIN_TERRITOIRE"]')
            ->getQuery()
            ->getResult();
    }

    public function findAdminsEmailByTerritory(Territory $territory): array
    {
        $queryBuilder = $this->createQueryBuilder('u');
        $queryBuilder
            ->select('u.email')
            ->where('u.roles LIKE :role OR u.roles LIKE :role2')
            ->setParameter('role', '["ROLE_ADMIN"]')
            ->setParameter('role2', '["ROLE_ADMIN_TERRITORY"]')
            ->andWhere('u.territory = :territory')
            ->setParameter('territory', $territory)
            ->andWhere('u.statut = '.User::STATUS_ACTIVE)
            ->andWhere('u.isMailingActive = true');

        $adminsEmail = array_map(function ($value) {
            return $value['email'];
        }, $queryBuilder->getQuery()->getArrayResult());

        return $adminsEmail;
    }

    public function findAllInactive(Territory|null $territory)
    {
        $queryBuilder = $this->createQueryBuilder('u');
        $queryBuilder
            ->where('u.statut = :inactive')
            ->setParameter('inactive', User::STATUS_INACTIVE);

        if (!empty($territory)) {
            $queryBuilder
                ->andWhere('u.territory = :territory')
                ->setParameter('territory', $territory);
        }

        return $queryBuilder->getQuery()->getResult();
    }

    public function findInactiveWithNbAffectationPending(): array
    {
        $connection = $this->getEntityManager()->getConnection();

        $sql = 'SELECT u.email, count(*) as nb_signalements, u.created_at, GROUP_CONCAT(a.signalement_id) as signalements
                FROM user u
                LEFT JOIN affectation a  on a.partner_id = u.partner_id and a.statut = 0
                WHERE u.statut = 0 AND DATE(u.created_at) <= (DATE(NOW()) - INTERVAL 10 DAY)
                GROUP BY u.email, u.created_at
                ORDER BY nb_signalements desc';

        $statetment = $connection->prepare($sql);

        $pendingUsers = $statetment->executeQuery()->fetchAllAssociative();

        return array_map(function ($pendingUser) {
            return [
                'email' => $pendingUser['email'],
                'nb_signalements' => (!empty($pendingUser['signalements'])) ? (int) $pendingUser['nb_signalements'] : 0,
                'created_at' => $pendingUser['created_at'],
            ];
        }, $pendingUsers);
    }

    public function findAllArchived(
        Territory|null $territory,
        Partner|null $partner,
        ?string $filterTerms,
        bool $includeUsagers,
        $page
    ) {
        $maxResult = User::MAX_LIST_PAGINATION;
        $firstResult = ($page - 1) * $maxResult;

        $queryBuilder = $this->createQueryBuilder('u');
        $queryBuilder
            ->where('u.statut = :archived')
            ->setParameter('archived', User::STATUS_ARCHIVE);

        if (!empty($territory)) {
            $queryBuilder
                ->andWhere('u.territory = :territory')
                ->setParameter('territory', $territory);
        }

        if (!empty($partner)) {
            $queryBuilder
                ->andWhere('u.partner = :partner')
                ->setParameter('partner', $partner);
        }

        if (!empty($filterTerms)) {
            $queryBuilder
                ->andWhere('LOWER(u.nom) LIKE :usersterms
                OR LOWER(u.prenom) LIKE :usersterms
                OR LOWER(u.email) LIKE :usersterms');
            $queryBuilder
                ->setParameter('usersterms', '%'.strtolower($filterTerms).'%');
        }

        if (!$includeUsagers) {
            $queryBuilder
                ->andWhere('u.roles LIKE :role
                OR u.roles LIKE :role2
                OR u.roles LIKE :role3');
            $queryBuilder
                ->setParameter('role', '%ROLE_ADMIN%')
                ->setParameter('role2', '%ROLE_ADMIN_TERRITORY%')
                ->setParameter('role3', '%ROLE_USER_PARTNER%');
        }

        $queryBuilder->setFirstResult($firstResult)->setMaxResults($maxResult);

        return new Paginator($queryBuilder->getQuery(), false);
    }

    /**
     * @throws NonUniqueResultException
     */
    public function countUserByStatus(?Territory $territory = null): CountUser
    {
        $qb = $this->createQueryBuilder('u');
        $qb->select(sprintf('NEW %s(
            SUM(CASE WHEN u.statut = :active THEN 1 ELSE 0 END),
            SUM(CASE WHEN u.statut = :inactive THEN 1 ELSE 0 END))',
            CountUser::class))
            ->setParameter('active', User::STATUS_ACTIVE)
            ->setParameter('inactive', User::STATUS_INACTIVE)
            ->where('u.statut != :statut')
            ->setParameter('statut', User::STATUS_ARCHIVE);

        if (null !== $territory) {
            $qb->andWhere('u.territory = :territory')->setParameter('territory', $territory);
        }

        return $qb->getQuery()->getOneOrNullResult();
    }
}
