<?php

namespace App\Repository;

use App\Command\Cron\RetryFailedEmailsCommand;
use App\Entity\FailedEmail;
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
class FailedEmailRepository extends ServiceEntityRepository
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
        $startAt = new \DateTimeImmutable(RetryFailedEmailsCommand::START_AT_YEAR.'-01-01 00:00:00');

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
}
