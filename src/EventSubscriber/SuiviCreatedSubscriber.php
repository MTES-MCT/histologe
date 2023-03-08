<?php

namespace App\EventSubscriber;

use App\Entity\SignalementAnalytics;
use App\Entity\Suivi;
use App\Repository\SignalementAnalyticsRepository;
use Doctrine\Bundle\DoctrineBundle\EventSubscriber\EventSubscriberInterface;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Events;

class SuiviCreatedSubscriber implements EventSubscriberInterface
{
    public function __construct(private readonly SignalementAnalyticsRepository $signalementAnalyticsRepository)
    {
    }

    public function getSubscribedEvents(): array
    {
        return [
            Events::onFlush,
        ];
    }

    public function onFlush(OnFlushEventArgs $eventArgs): void
    {
        $entityManager = $eventArgs->getObjectManager();
        $unitOfWork = $entityManager->getUnitOfWork();

        /** @var Suivi $entity */
        foreach ($unitOfWork->getScheduledEntityInsertions() as $entity) {
            if ($this->supports($entity)) {
                $signalement = $entity->getSignalement();
                $signalementAnalytics = $this->signalementAnalyticsRepository->findOneBy(['signalement' => $signalement]);

                if (null === $signalementAnalytics) {
                    $signalementAnalytics = (new SignalementAnalytics())
                        ->setSignalement($entity->getSignalement())
                        ->setLastSuiviAt($entity->getCreatedAt())
                        ->setLastSuiviUserBy($entity->getCreatedBy());
                } else {
                    $signalementAnalytics
                        ->setLastSuiviAt($entity->getCreatedAt())
                        ->setLastSuiviUserBy($entity->getCreatedBy());
                }
                $metaData = $entityManager->getClassMetadata(SignalementAnalytics::class);
                $entityManager->persist($signalementAnalytics);
                $unitOfWork->computeChangeSet($metaData, $signalementAnalytics);
            }
        }
    }

    public function supports($entity): bool
    {
        return $entity instanceof Suivi;
    }
}
