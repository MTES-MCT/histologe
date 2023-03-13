<?php

namespace App\EventSubscriber;

use App\Entity\Signalement;
use App\Entity\Suivi;
use App\Service\Signalement\SuiviHelper;
use Doctrine\Bundle\DoctrineBundle\EventSubscriber\EventSubscriberInterface;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Events;

class SuiviCreatedSubscriber implements EventSubscriberInterface
{
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
                $signalement->setLastSuiviAt($entity->getCreatedAt());
                $signalement->setLastSuiviBy(SuiviHelper::getSuiviLastByLabel($signalement));
                $metaData = $entityManager->getClassMetadata(Signalement::class);
                $entityManager->persist($signalement);
                //  used to recompute the changes of a specific signalement entity
                $unitOfWork->recomputeSingleEntityChangeSet($metaData, $signalement);
            }
        }
    }

    public function supports($entity): bool
    {
        return $entity instanceof Suivi;
    }
}
