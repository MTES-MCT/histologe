<?php

namespace App\Repository;

use App\Entity\Territory;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
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

        $sql = 'SELECT u.email, count(*) as nb_signalements, GROUP_CONCAT(a.signalement_id) as signalements
                FROM user u
                LEFT JOIN affectation a  on a.partner_id = u.partner_id and a.statut = 0
                WHERE u.statut = 0
                GROUP BY u.email
                ORDER BY nb_signalements desc';

        $statetment = $connection->prepare($sql);

        return $statetment->executeQuery()->fetchAllAssociative();
    }
}
