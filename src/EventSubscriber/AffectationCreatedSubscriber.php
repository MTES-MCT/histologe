<?php

namespace App\EventSubscriber;

use App\Event\AffectationCreatedEvent;
use App\Service\NotificationAndMailSender;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class AffectationCreatedSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly NotificationAndMailSender $notificationAndMailSender,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            AffectationCreatedEvent::NAME => 'onAffectationCreated',
        ];
    }

    public function onAffectationCreated(AffectationCreatedEvent $event): void
    {
        $affectation = $event->getAffectation();

        $this->notificationAndMailSender->sendNewAffectation($affectation);
    }
}
