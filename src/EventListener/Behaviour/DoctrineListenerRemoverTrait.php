<?php

namespace App\EventListener\Behaviour;

use Doctrine\Common\EventManager;

trait DoctrineListenerRemoverTrait
{
    public function removeListener(EventManager $eventManager, string $listenerClass, string $event): void
    {
        foreach ($eventManager->getListeners($event) as $listener) {
            if ($listener instanceof $listenerClass) {
                $eventManager->removeEventListener([$event], $listener);
            }
        }
    }

    public function removeListeners(EventManager $eventManager, string $listenerClass, array $events): void
    {
        foreach ($events as $event) {
            $this->removeListener($eventManager, $listenerClass, $event);
        }
    }
}
