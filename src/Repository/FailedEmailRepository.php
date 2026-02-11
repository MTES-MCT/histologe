<?php

namespace App\Repository;

use App\Command\Cron\RetryFailedEmailsCommand;
use App\Entity\FailedEmail;
use App\Repository\Behaviour\EntityCleanerRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<FailedEmail>
 *
 * @method FailedEmail|null find($id, $lockMode = null, $lockVersion = null)
 * @method FailedEmail|null findOneBy(array $criteria, array $orderBy = null)
 * @method FailedEmail[]    findAll()
 * @method FailedEmail[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class FailedEmailRepository extends ServiceEntityRepository implements EntityCleanerRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FailedEmail::class);
    }

    /**
     * @return array<int, FailedEmail>
     */
    public function findEmailToResend(): array
    {
        $startAt = new \DateTimeImmutable(FailedEmail::EXPIRATION_PERIOD);

        return $this->createQueryBuilder('f')
            ->where('f.isResendSuccessful = :isResendSuccessful')
            ->setParameter('isResendSuccessful', false)
            ->andWhere('f.createdAt > :createdAt')
            ->setParameter('createdAt', $startAt)
            ->andWhere('f.errorMessage NOT IN (:errors)')
            ->setParameter('errors', RetryFailedEmailsCommand::ERRORS_TO_IGNORE)
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * @throws \Exception
     */
    public function cleanOlderThan(string $period = FailedEmail::EXPIRATION_PERIOD): int
    {
        $queryBuilder = $this->createQueryBuilder('f');
        $queryBuilder->delete()
            ->andWhere('DATE(f.createdAt) <= :createdAt')
            ->setParameter('createdAt', (new \DateTimeImmutable($period))->format('Y-m-d'));

        return $queryBuilder->getQuery()->execute();
    }
}
