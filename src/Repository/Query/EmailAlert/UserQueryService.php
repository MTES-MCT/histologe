<?php

namespace App\Repository\Query\EmailAlert;

use App\Entity\EmailDeliveryIssue;
use App\Entity\Signalement;
use Doctrine\ORM\EntityManagerInterface;

class UserQueryService
{
    public const string OCCUPANT = 'occupant';
    public const string DECLARANT = 'declarant';

    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    public function countUserWithEmailIssue(string $typeUsager = self::OCCUPANT, ?string $email = null): int
    {
        if (null === $email) {
            return 0;
        }

        $mailField = self::OCCUPANT === $typeUsager ? 's.mailOccupant' : 's.mailDeclarant';
        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('COUNT(s.id)')
            ->from(Signalement::class, 's')
            ->innerJoin(
                EmailDeliveryIssue::class,
                'edi',
                'WITH', $qb->expr()->eq('edi.email', $mailField)
            )
            ->where($qb->expr()->eq($mailField, ':email'))
            ->setParameter('email', $email);

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    public function shouldDisplayAlertEmailIssue(string $typeUsager = self::OCCUPANT, ?string $email = null): bool
    {
        if (null === $email) {
            return false;
        }

        return $this->countUserWithEmailIssue($typeUsager, $email) > 0;
    }

    /**
     * @param array<string> $emails
     *
     * @return array<string>
     */
    public function findEmailsWithIssue(array $emails): array
    {
        if (empty($emails)) {
            return [];
        }

        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('edi.email AS email')
            ->from(EmailDeliveryIssue::class, 'edi')
            ->where('edi.email IN (:emails)')
            ->setParameter('emails', $emails);

        return $qb->getQuery()->getSingleColumnResult();
    }
}
