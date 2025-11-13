<?php

namespace App\Repository;

use App\Entity\Enum\UserStatus;
use App\Entity\Signalement;
use App\Entity\Territory;
use App\Entity\User;
use App\Service\Gouv\ProConnect\Model\ProConnectUser;
use App\Service\ListFilters\SearchArchivedUser;
use App\Service\ListFilters\SearchUser;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Clock\ClockInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;

/**
 * @extends ServiceEntityRepository<User>
 *
 * @method User|null find($id, $lockMode = null, $lockVersion = null)
 * @method User|null findOneBy(array $criteria, array $orderBy = null)
 * @method User[]    findAll()
 * @method User[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserRepository extends ServiceEntityRepository implements PasswordUpgraderInterface
{
    public function __construct(
        ManagerRegistry $registry,
        private readonly TerritoryRepository $territoryRepository,
        private readonly PartnerRepository $partnerRepository,
        private readonly EmailDeliveryIssueRepository $emailDeliveryIssueRepository,
        private readonly ClockInterface $clock,
        #[Autowire(env: 'USER_SYSTEM_EMAIL')]
        private readonly string $userSystemEmail,
    ) {
        parent::__construct($registry, User::class);
    }

    public function save(User $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Used to upgrade (rehash) the user's password automatically over time.
     */
    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(\sprintf('Instances of "%s" are not supported.', $user::class));
        }

        $user->setPassword($newHashedPassword);
        $this->_em->persist($user);
        $this->_em->flush();
    }

    /**
     * @return array<int, User>|null
     */
    public function findActiveAdmins(): ?array
    {
        $queryBuilder = $this->createQueryBuilder('u');

        return $queryBuilder
            ->andWhere('JSON_CONTAINS(u.roles, :role_admin) = 1')
            ->setParameter('role_admin', '"ROLE_ADMIN"')
            ->andWhere('u.statut = :active')
            ->setParameter('active', UserStatus::ACTIVE)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return array<int, User>|null
     */
    public function findActiveAdminsAndTerritoryAdmins(?Territory $territory): ?array
    {
        $queryBuilder = $this->createQueryBuilder('u');

        $queryBuilder
            ->innerJoin('u.userPartners', 'up')
            ->innerJoin('up.partner', 'p')
            ->andWhere('JSON_CONTAINS(u.roles, :role) = 1 OR (JSON_CONTAINS(u.roles, :role2) = 1 AND p.territory = :territory)')
                ->setParameter('role', '"ROLE_ADMIN"')
                ->setParameter('role2', '"ROLE_ADMIN_TERRITORY"')
                ->setParameter('territory', $territory)
            ->andWhere('u.statut LIKE :active')
                ->setParameter('active', UserStatus::ACTIVE);

        return $queryBuilder->getQuery()
            ->getResult();
    }

    /**
     * @return array<array{id: int, partner_id: int}>
     */
    public function findAllUnarchivedUserPartnerOrAdminPartner(): ?array
    {
        $sql = "
            SELECT u.id, up.partner_id
            FROM user u
            INNER JOIN user_partner up ON u.id = up.user_id
            WHERE u.statut != '".UserStatus::ARCHIVE->value."'
            AND (JSON_CONTAINS(u.roles, '\"ROLE_ADMIN_PARTNER\"') = 1 OR JSON_CONTAINS(u.roles, '\"ROLE_USER_PARTNER\"') = 1)
        ";

        $connection = $this->getEntityManager()->getConnection();
        $stmt = $connection->prepare($sql);

        return $stmt->executeQuery()->fetchAllAssociative(); // @phpstan-ignore-line
    }

    public function findArchivedUserByEmail(string $email): ?User
    {
        $queryBuilder = $this->createQueryBuilder('u');

        return $queryBuilder
            ->andWhere('u.email LIKE :email')
            ->setParameter('email', $email.'%')
            ->andWhere('u.statut LIKE :archived')
            ->setParameter('archived', UserStatus::ARCHIVE)
            ->andWhere('u.anonymizedAt IS NULL')
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @return array<int, User>|null
     */
    public function findAnonymizedUsers(): ?array
    {
        $queryBuilder = $this->createQueryBuilder('u');

        return $queryBuilder
            ->andWhere('u.anonymizedAt IS NOT NULL')
            ->getQuery()
            ->getResult();
    }

    public function findOneByEmailExcepted(string $email, User $user): ?User
    {
        $queryBuilder = $this->createQueryBuilder('u');

        return $queryBuilder
            ->andWhere('u.email = :email')
            ->setParameter('email', $email)
            ->andWhere('u.id != :id')
            ->setParameter('id', $user->getId())
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @return array<int, User>
     */
    public function findActiveTerritoryAdmins(int $territoryId, ?string $inseeOccupant = null): array
    {
        $queryBuilder = $this->createQueryBuilder('u')
            ->leftJoin('u.userPartners', 'up')
            ->leftJoin('up.partner', 'p')
            ->andWhere('p.territory = :territory')
            ->setParameter('territory', $territoryId)
            ->andWhere('JSON_CONTAINS(u.roles, :role) = 1 ')
            ->setParameter('role', '"ROLE_ADMIN_TERRITORY"')
            ->andWhere('u.statut = :active')
            ->setParameter('active', UserStatus::ACTIVE);

        if ($inseeOccupant) {
            $queryBuilder->andWhere(
                $queryBuilder->expr()->orX(
                    $queryBuilder->expr()->like('p.insee', "'[\"\"]'"),
                    $queryBuilder->expr()->like('p.insee', "'[]'"),
                    $queryBuilder->expr()->like('p.insee', ':insee')
                )
            )
            ->setParameter('insee', '%'.$inseeOccupant.'%');
        }

        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * @return array<int, array<string, int|string>>
     */
    public function findInactiveWithNbAffectationPending(): array
    {
        $connection = $this->getEntityManager()->getConnection();

        $sql = 'SELECT u.email, count(*) as nb_signalements, u.created_at, GROUP_CONCAT(a.signalement_id) as signalements
                FROM user u
                LEFT JOIN user_partner up ON up.user_id = u.id
                LEFT JOIN affectation a ON a.partner_id = up.partner_id AND a.statut = 0
                WHERE u.statut LIKE \''.UserStatus::INACTIVE->value.'\' AND DATE(u.created_at) <= (DATE(NOW()) - INTERVAL 10 DAY)
                AND u.roles NOT LIKE "%ROLE_USAGER%"
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

    /**
     * @return Paginator<User>
     */
    public function findArchivedFilteredPaginated(SearchArchivedUser $searchArchivedUser, int $maxResult): Paginator
    {
        $queryBuilder = $this->createQueryBuilder('u');
        $queryBuilder->select('u', 'up', 'p')
            ->leftJoin('u.userPartners', 'up')
            ->leftJoin('up.partner', 'p');
        $queryBuilder->andWhere('u.anonymizedAt IS NULL');

        $isNonePartner = ('none' === $searchArchivedUser->getPartner());
        if ($isNonePartner) {
            $queryBuilder
                ->andWhere('up.id IS NULL');
        } else {
            $territory = $searchArchivedUser->getTerritory() ? $this->territoryRepository->find($searchArchivedUser->getTerritory()) : null;
            $partner = $searchArchivedUser->getPartner() ? $this->partnerRepository->find($searchArchivedUser->getPartner()) : null;

            $builtOrCondition = '';
            if (empty($territory)) {
                $builtOrCondition .= ' OR p.territory IS NULL';
            }
            if (empty($partner)) {
                $builtOrCondition .= ' OR up.id IS NULL';
            }

            $queryBuilder
                ->andWhere('u.statut = :archived'.$builtOrCondition)
                ->setParameter('archived', UserStatus::ARCHIVE);

            if (!empty($territory)) {
                $queryBuilder
                    ->andWhere('p.territory = :territory')
                    ->setParameter('territory', $territory);
            }

            if (!empty($partner)) {
                $queryBuilder
                    ->andWhere('up.partner = :partner')
                    ->setParameter('partner', $partner);
            }
        }

        $filterTerms = $searchArchivedUser->getQueryUser();
        if (!empty($filterTerms)) {
            $queryBuilder
                ->andWhere('LOWER(u.nom) LIKE :usersterms
                OR LOWER(u.prenom) LIKE :usersterms
                OR LOWER(u.email) LIKE :usersterms');
            $queryBuilder
                ->setParameter('usersterms', '%'.strtolower($filterTerms).'%');
        }

        $queryBuilder
            ->andWhere('u.roles LIKE :role
            OR u.roles LIKE :role2
            OR u.roles LIKE :role3');
        $queryBuilder
            ->setParameter('role', '%ROLE_ADMIN_PARTNER%')
            ->setParameter('role2', '%ROLE_ADMIN_TERRITORY%')
            ->setParameter('role3', '%ROLE_USER_PARTNER%');

        if (!empty($searchArchivedUser->getOrderType())) {
            [$orderField, $orderDirection] = explode('-', $searchArchivedUser->getOrderType());
            $queryBuilder->orderBy($orderField, $orderDirection);
        } else {
            $queryBuilder->orderBy('u.nom', 'ASC');
        }

        $firstResult = ($searchArchivedUser->getPage() - 1) * $maxResult;
        $queryBuilder->setFirstResult($firstResult)->setMaxResults($maxResult);

        return new Paginator($queryBuilder->getQuery(), false);
    }

    /**
     * @return array<int, User>
     */
    public function findExpiredUsagers(string $limitConservation = '5 years'): array
    {
        $dateLimit = new \DateTimeImmutable('-'.$limitConservation);
        // retourne les usagers :
        // - non connectés depuis plus de limitConservation
        // - et n'etant pas occupant ou declarant sur des signalements actif (creation/edition/dernier suivi) depuis limitConservation
        $qb = $this->createQueryBuilder('u')
            ->select('u')
            ->leftJoin('u.signalementUsagerDeclarants', 'sud')
            ->leftJoin('u.signalementUsagerOccupants', 'suo')
            ->leftJoin('sud.signalement', 'sd')
            ->leftJoin('suo.signalement', 'so')

            ->where('(u.lastLoginAt IS NOT NULL AND u.lastLoginAt < :dateLimit) OR (u.lastLoginAt IS NULL AND u.createdAt < :dateLimit)')
            ->andWhere('sd.createdAt IS NULL OR (sd.createdAt < :dateLimit AND so.createdAt < :dateLimit)')
            ->andWhere('(sd.modifiedAt IS NULL OR sd.modifiedAt < :dateLimit) AND (so.modifiedAt IS NULL OR so.modifiedAt < :dateLimit)')
            ->andWhere('(sd.lastSuiviAt IS NULL OR sd.lastSuiviAt < :dateLimit) AND (so.lastSuiviAt IS NULL OR so.lastSuiviAt < :dateLimit)')
            ->andWhere('JSON_CONTAINS(u.roles, :roles) = 1')

            ->setParameter('dateLimit', $dateLimit)
            ->setParameter('roles', '"ROLE_USAGER"');

        return $qb->getQuery()->execute();
    }

    /**
     * @return array<int, User>
     */
    public function findExpiredUsers(): array
    {
        $qb = $this->getQueryBuilerForinactiveUsersSince('2 years');
        $qb->andWhere('u.anonymizedAt IS NULL');
        $qb->andWhere('u.statut = :statut')->setParameter('statut', UserStatus::ARCHIVE);

        return $qb->getQuery()->execute();
    }

    /**
     * @return array<int, User>
     */
    public function findInactiveUsers(): array
    {
        $qb = $this->getQueryBuilerForinactiveUsersSince('1 year');

        $qb->andWhere('u.statut != :statut')->setParameter('statut', UserStatus::ARCHIVE);
        $qb->andWhere('u.anonymizedAt IS NULL');
        $qb->andWhere('u.archivingScheduledAt IS NULL');
        $qb->andWhere('u.email != :userSystemEmail')->setParameter('userSystemEmail', $this->userSystemEmail);

        return $qb->getQuery()->execute();
    }

    private function getQueryBuilerForinactiveUsersSince(string $limitConservation): QueryBuilder
    {
        $dateLimit = new \DateTimeImmutable('-'.$limitConservation);
        $qb = $this->createQueryBuilder('u')
            ->select('u', 'up', 'p', 't')
            ->leftJoin('u.userPartners', 'up')
            ->leftJoin('up.partner', 'p')
            ->leftJoin('p.territory', 't')

            ->where('(u.lastLoginAt IS NOT NULL AND u.lastLoginAt < :dateLimit) OR (u.lastLoginAt IS NULL AND u.createdAt < :dateLimit)')
            ->andWhere('JSON_CONTAINS(u.roles, :roles) = 0')

            ->setParameter('dateLimit', $dateLimit)
            ->setParameter('roles', '"ROLE_USAGER"');

        return $qb;
    }

    /**
     * @return array<int, User>
     */
    public function findUsersToArchive(): array
    {
        $qb = $this->createQueryBuilder('u')
            ->where('u.archivingScheduledAt IS NOT NULL')
            ->andWhere('u.archivingScheduledAt < :date')
            ->setParameter('date', $this->clock->now());

        return $qb->getQuery()->execute();
    }

    /**
     * @param array<int, Territory> $territories
     *
     * @return array<int, User>|int
     */
    public function findUsersPendingToArchive(User $user, array $territories = [], bool $count = false): array|int
    {
        $qb = $this->createQueryBuilder('u')
            ->leftJoin('u.userPartners', 'up')
            ->leftJoin('up.partner', 'p')
            ->where('u.archivingScheduledAt IS NOT NULL');

        if (\count($territories)) {
            $qb->andWhere('p.territory IN (:territories)')->setParameter('territories', $territories);
        } elseif (!$user->isSuperAdmin()) {
            $qb->andWhere('p.territory IN (:territories)')->setParameter('territories', $user->getPartnersTerritories());
        }

        if ($count) {
            $qb->select('COUNT(u)');

            return (int) $qb->getQuery()->getSingleScalarResult();
        }
        $qb->orderBy('u.nom', 'ASC');

        return $qb->getQuery()->execute();
    }

    /**
     * @param array<int, Territory> $territories
     */
    public function countAgentsPbEmail(User $user, array $territories = []): int
    {
        $qb = $this->createQueryBuilder('u')
            ->leftJoin('u.userPartners', 'up')
            ->leftJoin('up.partner', 'p')
            ->andWhere('JSON_CONTAINS(u.roles, :roleUsager) = 0')
            ->setParameter('roleUsager', '"ROLE_USAGER"');

        if (\count($territories)) {
            $qb->andWhere('p.territory IN (:territories)')->setParameter('territories', $territories);
        } elseif (!$user->isSuperAdmin()) {
            $qb->andWhere('p.territory IN (:territories)')->setParameter('territories', $user->getPartnersTerritories());
        }

        $existsByEmailDql = $this->emailDeliveryIssueRepository->getExistsByEmailDql('u.email');

        $qb->andWhere($qb->expr()->exists($existsByEmailDql));
        $qb->select('COUNT(u.id)');

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * @return Paginator<User>
     */
    public function findFilteredPaginated(SearchUser $searchUser, int $maxResult): Paginator
    {
        /** @var QueryBuilder $qb */
        $qb = $this->findFiltered($searchUser, false);

        $firstResult = ($searchUser->getPage() - 1) * $maxResult;
        $qb->setFirstResult($firstResult)->setMaxResults($maxResult);

        return new Paginator($qb->getQuery());
    }

    /**
     * @return QueryBuilder|array<int, User>
     */
    public function findFiltered(SearchUser $searchUser, bool $execute = true): QueryBuilder|array
    {
        $qb = $this->createQueryBuilder('u');
        $qb->select('u', 'up', 'p', 't')
            ->leftJoin('u.userPartners', 'up')
            ->leftJoin('up.partner', 'p')
            ->leftJoin('p.territory', 't');

        if (!empty($searchUser->getOrderType())) {
            [$orderField, $orderDirection] = explode('-', $searchUser->getOrderType());
            $qb->orderBy($orderField, $orderDirection);
        } else {
            $qb->orderBy('u.nom', 'ASC');
        }

        $qb->andWhere('u.statut != :statutArchive')->setParameter('statutArchive', UserStatus::ARCHIVE);

        $qb->andWhere('JSON_CONTAINS(u.roles, :roleUsager) = 0')->setParameter('roleUsager', '"ROLE_USAGER"');
        $qb->andWhere('JSON_CONTAINS(u.roles, :roleApi) = 0')->setParameter('roleApi', '"ROLE_API_USER"');
        if (!$searchUser->getUser()->isSuperAdmin()) {
            $qb->andWhere('JSON_CONTAINS(u.roles, :roleAdmin) = 0')->setParameter('roleAdmin', '"ROLE_ADMIN"');
        }
        if ($searchUser->getQueryUser()) {
            $qb->andWhere('LOWER(u.nom) LIKE :queryUser
                OR LOWER(u.prenom) LIKE :queryUser
                OR LOWER(u.email) LIKE :queryUser');
            $qb->setParameter('queryUser', '%'.strtolower($searchUser->getQueryUser()).'%');
        }
        if ($searchUser->getTerritory()) {
            $qb->andWhere('p.territory = :territory')->setParameter('territory', $searchUser->getTerritory());
        } elseif (!$searchUser->getUser()->isSuperAdmin()) {
            $qb->andWhere('p.territory IN (:territories)')
                ->setParameter('territories', $searchUser->getUser()->getPartnersTerritories());
        }
        if ($searchUser->getPartners()->count() > 0) {
            $qb->andWhere('up.partner IN (:partners)')->setParameter('partners', $searchUser->getPartners());
        }
        if (null !== $searchUser->getPartnerType()) {
            $qb->andWhere('p.type = :partnerType')->setParameter('partnerType', $searchUser->getPartnerType());
        }
        if (null !== $searchUser->getStatut()) {
            $qb->andWhere('u.statut = :statut')->setParameter('statut', $searchUser->getStatut());
        }
        if ($searchUser->getRole()) {
            $qb->andWhere('JSON_CONTAINS(u.roles, :role) = 1 ')->setParameter('role', '"'.$searchUser->getRole().'"');
        }
        if ('Oui' == $searchUser->getPermissionAffectation()) {
            $qb->andWhere('u.hasPermissionAffectation = 1 OR JSON_CONTAINS(u.roles, :roleAdmin) = 1 OR JSON_CONTAINS(u.roles, :roleAdminTerritory) = 1')
                ->setParameter('roleAdmin', '"ROLE_ADMIN"')
                ->setParameter('roleAdminTerritory', '"ROLE_ADMIN_TERRITORY"');
        } elseif ('Non' == $searchUser->getPermissionAffectation()) {
            $qb->andWhere('u.hasPermissionAffectation = 0 AND JSON_CONTAINS(u.roles, :roleAdmin) = 0 AND JSON_CONTAINS(u.roles, :roleAdminTerritory) = 0')
                ->setParameter('roleAdmin', '"ROLE_ADMIN"')
                ->setParameter('roleAdminTerritory', '"ROLE_ADMIN_TERRITORY"');
        }
        if (null !== $searchUser->getEmailDeliveryIssue()) {
            $existsByEmailDql = $this->emailDeliveryIssueRepository->getExistsByEmailDql('u.email');
            if ('Oui' === $searchUser->getEmailDeliveryIssue()) {
                $qb->andWhere($qb->expr()->exists($existsByEmailDql));
            } else {
                $qb->andWhere($qb->expr()->not($qb->expr()->exists($existsByEmailDql)));
            }
        }
        if ($execute) {
            return $qb->getQuery()->execute();
        }

        return $qb;
    }

    public function findUsersApiQueryBuilder(SearchUser $searchUser): QueryBuilder
    {
        $qb = $this->createQueryBuilder('u');

        $qb->select('u', 'uap')
            ->leftJoin('u.userApiPermissions', 'uap')
            ->andWhere('JSON_CONTAINS(u.roles, :roles) = 1')
            ->andWhere('u.statut = :statutActive OR u.statut = :statutInactive')
            ->setParameter('statutActive', UserStatus::ACTIVE)
            ->setParameter('statutInactive', UserStatus::INACTIVE)
            ->setParameter('roles', '"ROLE_API_USER"');

        if ($searchUser->getQueryUser()) {
            $qb->andWhere('LOWER(u.email) LIKE :queryUser');
            $qb->setParameter('queryUser', '%'.strtolower($searchUser->getQueryUser()).'%');
        }

        if (null !== $searchUser->getStatut()) {
            $qb->andWhere('u.statut = :statut')->setParameter('statut', $searchUser->getStatut());
        }

        return $qb;
    }

    /**
     * @return Paginator<User>
     */
    public function findUsersApiPaginator(SearchUser $searchUser, int $maxResult = 20): Paginator
    {
        $queryBuilder = $this->findUsersApiQueryBuilder($searchUser);

        $firstResult = ($searchUser->getPage() - 1) * $maxResult;
        $queryBuilder->setFirstResult($firstResult)->setMaxResults($maxResult);

        return new Paginator($queryBuilder->getQuery(), false);
    }

    public function findAgentByEmail(string $email, ?UserStatus $userStatus = null, bool $acceptRoleApi = true): ?User
    {
        $queryBuilder = $this->createQueryBuilder('u')
            ->andWhere('u.email = :email')
            ->setParameter('email', $email)
            ->andWhere('JSON_CONTAINS(u.roles, :roles) = 0')
            ->setParameter('roles', '"ROLE_USAGER"');
        if (!$acceptRoleApi) {
            $queryBuilder->andWhere('JSON_CONTAINS(u.roles, :roleApi) = 0')
                ->setParameter('roleApi', '"ROLE_API_USER"');
        }

        if (null !== $userStatus) {
            $queryBuilder
                ->andWhere('u.statut = :userStatus')
                ->setParameter('userStatus', $userStatus);
        }

        return $queryBuilder->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @return array<int, User>
     */
    public function findUsersSubscribedToSignalement(Signalement $signalement, ?bool $onlyRT = false): array
    {
        $queryBuilder = $this->createQueryBuilder('u')
            ->innerJoin('u.userSignalementSubscriptions', 'uss')
            ->where('uss.signalement = :signalement')
            ->setParameter('signalement', $signalement)
            ->andWhere('u.statut = :userStatus')
            ->setParameter('userStatus', UserStatus::ACTIVE);
        if ($onlyRT) {
            $queryBuilder->andWhere('JSON_CONTAINS(u.roles, :roleRT) = 1')
                ->setParameter('roleRT', '"ROLE_ADMIN_TERRITORY"');
        }

        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * @return array<int, User>
     */
    public function findUserWaitingSummaryEmail(): array
    {
        $queryBuilder = $this->createQueryBuilder('u')
            ->innerJoin('u.notifications', 'n')
            ->where('n.waitMailingSummary = 1')
            ->andWhere('u.statut = :active')
            ->setParameter('active', UserStatus::ACTIVE);

        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * @throws NonUniqueResultException
     */
    public function findByProConnectUser(ProConnectUser $proConnectUser): ?User
    {
        $queryBuilder = $this->createQueryBuilder('u')
            ->andWhere('(u.email = :email OR u.proConnectUserId = :proConnectUserId)')
            ->andWhere('u.statut = :statutActive OR u.statut = :statutInactive')
            ->setParameter('email', $proConnectUser->email)
            ->setParameter('proConnectUserId', $proConnectUser->sub)
            ->setParameter('statutActive', UserStatus::ACTIVE)
            ->setParameter('statutInactive', UserStatus::INACTIVE);

        return $queryBuilder->getQuery()->getOneOrNullResult();
    }
}
