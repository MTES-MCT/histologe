<?php

namespace App\EventListener;

use App\Entity\Signalement;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Event\PostUpdateEventArgs;
use Doctrine\ORM\Events;

#[AsEntityListener(event: Events::postUpdate, method: 'postUpdate', entity: Signalement::class)]
class SignalementUpdatedListener
{
    private $updateOccurred = false;

    public function getSubscribedEvents(): array
    {
        return [
            Events::onFlush,
        ];
    }

    public function postUpdate(Signalement $signalement, PostUpdateEventArgs $event): void
    {
        $unitOfWork = $event->getObjectManager()->getUnitOfWork();

        foreach ($unitOfWork->getEntityChangeSet($signalement) as $change) {
            if ($change[0] != $change[1]) {
                $this->updateOccurred = true;
                break;
            }
        }
    }

    public function updateOccurred(): bool
    {
        return $this->updateOccurred;
    }
}
