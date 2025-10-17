<?php

namespace App\EventListener;

use App\Entity\Signalement;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Event\PostUpdateEventArgs;
use Doctrine\ORM\Events;

#[AsEntityListener(event: Events::postUpdate, method: 'postUpdate', entity: Signalement::class)]
class SignalementUpdatedListener
{
    private bool $updateOccurred = false;

    public function postUpdate(Signalement $signalement, PostUpdateEventArgs $event): void
    {
        $unitOfWork = $event->getObjectManager()->getUnitOfWork();

        foreach ($unitOfWork->getEntityChangeSet($signalement) as $key => $change) {
            $old = $change[0];
            $new = $change[1];
            if ($old != $new) {
                $this->updateOccurred = true;
            }

            if ('mailOccupant' === $key) {
                $user = $signalement->getSignalementUsager()?->getOccupant();
                $user?->setEmail($new);
                $user?->setEmailDeliveryIssue(null);
            }

            if ('mailDeclarant' === $key) {
                $user = $signalement->getSignalementUsager()?->getDeclarant();
                $user?->setEmail($new);
                $user?->setEmailDeliveryIssue(null);
            }
        }
    }

    public function updateOccurred(): bool
    {
        return $this->updateOccurred;
    }
}
