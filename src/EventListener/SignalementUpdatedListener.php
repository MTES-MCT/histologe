<?php

namespace App\EventListener;

use App\Entity\Signalement;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Events;

#[AsDoctrineListener(event: Events::onFlush)]
class SignalementUpdatedListener
{
    private $updateOccurred = false;

    public function getSubscribedEvents(): array
    {
        return [
            Events::onFlush,
        ];
    }

    public function onFlush(OnFlushEventArgs $args): void
    {
        $unitOfWork = $args->getObjectManager()->getUnitOfWork();

        foreach ($unitOfWork->getScheduledEntityUpdates() as $entity) {
            $changes = $unitOfWork->getEntityChangeSet($entity);

            if ($entity instanceof Signalement) {
                foreach ($changes as $key => $change) {
                    if ($change[0] != $change[1]) {
                        $this->updateOccurred = true;
                    }
                }
            }
        }
    }

    public function updateOccurred(): bool
    {
        return $this->updateOccurred;
    }
}
