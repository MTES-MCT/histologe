<?php

namespace App\EventSubscriber;

use App\Event\SignalementCreatedEvent;
use App\Manager\FileManager;
use App\Manager\UserManager;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class SignalementCreatedSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private UserManager $userManager,
        private FileManager $fileManager,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            SignalementCreatedEvent::NAME => 'onSignalementCreated',
        ];
    }

    public function onSignalementCreated(SignalementCreatedEvent $event): void
    {
        $signalement = $event->getSignalement();

        $this->userManager->createUsagerFromSignalement($signalement, $this->userManager::OCCUPANT);
        $this->userManager->createUsagerFromSignalement($signalement, $this->userManager::DECLARANT);
    }
}
