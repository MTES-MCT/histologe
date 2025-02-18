<?php

namespace App\EventSubscriber;

use App\Entity\Enum\SignalementStatus;
use App\Entity\Suivi;
use App\Event\SuiviCreatedEvent;
use App\Service\NotificationAndMailSender;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class SuiviCreatedSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly NotificationAndMailSender $notificationAndMailSender,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            SuiviCreatedEvent::NAME => 'onSuiviCreated',
        ];
    }

    public function onSuiviCreated(SuiviCreatedEvent $event): void
    {
        $suivi = $event->getSuivi();

        // pas de notification pour un suivi technique ou si intervention
        if (Suivi::TYPE_TECHNICAL === $suivi->getType() || Suivi::CONTEXT_INTERVENTION === $suivi->getContext()) {
            return;
        }

        if (SignalementStatus::DRAFT === $suivi->getSignalement()->getStatut()
                || SignalementStatus::ARCHIVED === $suivi->getSignalement()->getStatut()) {
            return;
        }

        if (Suivi::CONTEXT_NOTIFY_USAGER_ONLY !== $suivi->getContext()) {
            $this->notificationAndMailSender->sendNewSuiviToAdminsAndPartners(
                suivi: $suivi,
                sendEmail: (SignalementStatus::CLOSED !== $suivi->getSignalement()->getStatut())
            );
        }

        if ($suivi->getSendMail()
                && $suivi->getIsPublic()
                && SignalementStatus::CLOSED !== $suivi->getSignalement()->getStatut()
                && SignalementStatus::REFUSED !== $suivi->getSignalement()->getStatut()) {
            $this->notificationAndMailSender->sendNewSuiviToUsagers($suivi);
        }
    }
}
