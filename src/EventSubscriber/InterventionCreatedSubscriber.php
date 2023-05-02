<?php

namespace App\EventSubscriber;

use App\Entity\Enum\InterventionType;
use App\Event\InterventionCreatedEvent;
use App\Service\Mailer\NotificationMailerType;
use App\Service\Signalement\VisiteNotifier;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class InterventionCreatedSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private VisiteNotifier $visiteNotifier,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            InterventionCreatedEvent::NAME => 'onInterventionCreated',
        ];
    }

    public function onInterventionCreated(InterventionCreatedEvent $event): void
    {
        $intervention = $event->getIntervention();
        if (InterventionType::VISITE === $intervention->getType()) {
            $description = 'Visite programmée : une visite du logement situé '.$intervention->getSignalement()->getAdresseOccupant();
            $description .= ' est prévue le '.$intervention->getDate()->format('d/m/Y').'.';
            $description .= '<br>';
            $description .= 'La visite sera effectuée par '.$intervention->getPartner()->getNom().'.';
            $suivi = $this->visiteNotifier->createSuivi(
                description: $description,
                currentUser: $event->getUser(),
                signalement: $intervention->getSignalement(),
            );

            $this->visiteNotifier->notifyUsagers($intervention, NotificationMailerType::TYPE_VISITE_CREATED_TO_USAGER);

            $this->visiteNotifier->notifyAgents(
                intervention: $intervention,
                suivi: $suivi,
                currentUser: $event->getUser(),
                notificationMailerType: NotificationMailerType::TYPE_VISITE_CREATED_TO_PARTNER,
            );
        }
    }
}
