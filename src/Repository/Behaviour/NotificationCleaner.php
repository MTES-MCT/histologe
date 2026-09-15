<?php

namespace App\Repository\Behaviour;

use App\Entity\Enum\NotificationType;
use App\Entity\Notification;
use Doctrine\ORM\EntityManagerInterface;

class NotificationCleaner implements EntityCleanerRepositoryInterface
{
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    public function getClassName(): string
    {
        return Notification::class;
    }

    /**
     * @throws \Exception
     */
    public function cleanOlderThan(string $period = Notification::EXPIRATION_PERIOD): int
    {
        $queryBuilder = $this->entityManager->createQueryBuilder();
        $queryBuilder->delete()
            ->from(Notification::class, 'n')
            ->andWhere('DATE(n.createdAt) <= :created_at')
            ->andWhere('n.type NOT LIKE :notification_type')
            ->setParameter('created_at', (new \DateTimeImmutable($period))->format('Y-m-d'))
            ->setParameter('notification_type', NotificationType::SUIVI_USAGER);

        return $queryBuilder->getQuery()->execute();
    }
}
