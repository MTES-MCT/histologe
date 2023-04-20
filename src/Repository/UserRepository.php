<?php

namespace App\Repository;

use App\Dto\CountUser;
use App\Entity\Partner;
use App\Entity\Territory;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\Tools\Pagination\Paginator;
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

    public function findActiveTerritoryAdmins(?Territory $territory): ?array
    {
        if (empty($territory)) {
            return null;
        }

        $queryBuilder = $this->createQueryBuilder('u');

        return $queryBuilder
            ->andWhere('u.territory = :territory')
            ->setParameter('territory', $territory)
            ->andWhere('u.roles LIKE :role')
            ->setParameter('role', '%ROLE_ADMIN_TERRITORY%')
            ->andWhere('u.statut LIKE :active')
            ->setParameter('active', User::STATUS_ACTIVE)
            ->getQuery()
            ->getResult();
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

        $statement = $connection->prepare($sql);

        $pendingUsers = $statement->executeQuery()->fetchAllAssociative();

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
        bool $isNoneTerritory,
        Partner|null $partner,
        bool $isNonePartner,
        ?string $filterTerms,
        bool $includeUsagers,
        $page
    ): Paginator {
        $maxResult = User::MAX_LIST_PAGINATION;
        $firstResult = ($page - 1) * $maxResult;

        $queryBuilder = $this->createQueryBuilder('u');

        if ($isNoneTerritory || $isNonePartner) {
            if ($isNoneTerritory) {
                $queryBuilder
                    ->where('u.territory IS NULL');
            }
            if ($isNonePartner) {
                $queryBuilder
                    ->andWhere('u.partner IS NULL');
            }
        } else {
            $builtOrCondition = '';
            if (empty($territory)) {
                $builtOrCondition .= ' OR u.territory IS NULL';
            }
            if (empty($partner)) {
                $builtOrCondition .= ' OR u.partner IS NULL';
            }

            $queryBuilder
                ->where('u.statut = :archived'.$builtOrCondition)
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
                ->setParameter('role', '%ROLE_ADMIN_PARTNER%')
                ->setParameter('role2', '%ROLE_ADMIN_TERRITORY%')
                ->setParameter('role3', '%ROLE_USER_PARTNER%');
        } else {
            $queryBuilder
                ->andWhere('u.roles NOT LIKE :roleadmin')
                ->setParameter('roleadmin', '%"ROLE_ADMIN%"');
        }

        $queryBuilder->setFirstResult($firstResult)->setMaxResults($maxResult);

        return new Paginator($queryBuilder->getQuery(), false);
    }

    /**
     * @throws NonUniqueResultException
     */
    public function countUserByStatus(?Territory $territory = null, ?User $user = null): CountUser
    {
        $qb = $this->createQueryBuilder('u');
        $qb->select(sprintf(
            'NEW %s(
            SUM(CASE WHEN u.statut = :active THEN 1 ELSE 0 END),
            SUM(CASE WHEN u.statut = :inactive THEN 1 ELSE 0 END))',
            CountUser::class
        ))
            ->setParameter('active', User::STATUS_ACTIVE)
            ->setParameter('inactive', User::STATUS_INACTIVE)
            ->where('u.statut != :statut')
            ->andWhere('u.roles not like :role')
            ->setParameter('statut', User::STATUS_ARCHIVE)
            ->setParameter('role', '%'.User::ROLE_USAGER.'%');

        if (null !== $territory) {
            $qb->andWhere('u.territory = :territory')->setParameter('territory', $territory);
        }

        if ($user?->isUserPartner() || $user?->isPartnerAdmin()) {
            $qb->andWhere('u.partner = :partner')->setParameter('partner', $user->getPartner());
        }

        return $qb->getQuery()->getOneOrNullResult();
    }
}
