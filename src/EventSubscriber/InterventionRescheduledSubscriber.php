<?php

namespace App\EventSubscriber;

use App\Entity\Enum\InterventionType;
use App\Event\InterventionRescheduledEvent;
use App\Service\Mailer\NotificationMailerType;
use App\Service\Signalement\VisiteNotifier;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class InterventionRescheduledSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private VisiteNotifier $visiteNotifier,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            InterventionRescheduledEvent::NAME => 'onInterventionRescheduled',
        ];
    }

    public function onInterventionRescheduled(InterventionRescheduledEvent $event): void
    {
        $intervention = $event->getIntervention();
        if (InterventionType::VISITE == $intervention->getType()) {
            // Creation of suivi
            $description = 'Changement de date de visite : la visite du logement initialement prévue le ';
            $description .= $event->getPreviousDate()->format('d/m/Y');
            $description .= ' a été décalée au ';
            $description .= $intervention->getDate()->format('d/m/Y').'.';
            $description .= '<br>';
            $description .= 'La visite sera effectuée par '.$intervention->getPartner()->getNom().'.';
            $suivi = $this->visiteNotifier->createSuivi(
                description: $description,
                currentUser: $event->getUser(),
                signalement: $intervention->getSignalement(),
            );

            // Send mails to usager
            $this->visiteNotifier->notifyUsagers(
                intervention: $intervention,
                notificationMailerType: NotificationMailerType::TYPE_VISITE_RESCHEDULED_TO_USAGER,
                previousDate: $event->getPreviousDate(),
            );

            // Send mails and notifications to agents
            $this->visiteNotifier->notifyAgents(
                intervention: $intervention,
                suivi: $suivi,
                currentUser: $event->getUser(),
                notificationMailerType: null,
            );
        }
    }
}
