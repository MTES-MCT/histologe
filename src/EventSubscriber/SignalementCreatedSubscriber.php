<?php

namespace App\EventSubscriber;

use App\Event\SignalementCreatedEvent;
use App\Manager\UserManager;
use App\Service\NotificationAndMailSender;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class SignalementCreatedSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly UserManager $userManager,
        private readonly NotificationAndMailSender $notificationAndMailSender,
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

        $this->userManager->createUsagerFromSignalement($signalement);
        $this->userManager->createUsagerFromSignalement($signalement, $this->userManager::DECLARANT);

        $this->notificationAndMailSender->sendNewSignalement($signalement);
    }
}
