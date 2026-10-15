<?php

namespace App\EventListener\Behaviour;

use Doctrine\Common\EventManagerInterface;

trait DoctrineListenerRemoverTrait
{
    public function removeListener(EventManagerInterface $eventManager, string $listenerClass, string $event): void
    {
        foreach ($eventManager->getListeners($event) as $listener) {
            if ($listener instanceof $listenerClass) {
                $eventManager->removeEventListener([$event], $listener);
            }
        }
    }

    /**
     * @param array<int, string> $events
     */
    public function removeListeners(EventManagerInterface $eventManager, string $listenerClass, array $events): void
    {
        foreach ($events as $event) {
            $this->removeListener($eventManager, $listenerClass, $event);
        }
    }
}
