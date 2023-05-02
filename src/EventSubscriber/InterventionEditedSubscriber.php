<?php

namespace App\EventSubscriber;

use App\Entity\Enum\InterventionType;
use App\Event\InterventionEditedEvent;
use App\Service\Mailer\NotificationMailerType;
use App\Service\Signalement\VisiteNotifier;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class InterventionEditedSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private Security $security,
        private VisiteNotifier $visiteNotifier,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            InterventionEditedEvent::NAME => 'onInterventionEdited',
        ];
    }

    public function onInterventionEdited(InterventionEditedEvent $event): void
    {
        $intervention = $event->getIntervention();
        if ($event->isUsagerNotified() && InterventionType::VISITE === $intervention->getType()) {
            $currentUser = $event->getUser();
            $description = 'Edition de la conclusion de la visite par '.$intervention->getPartner()->getNom().'.<br>';
            $description .= 'Commentaire opérateur :<br>';
            $description .= $intervention->getDetails();
            $suivi = $this->visiteNotifier->createSuivi(
                description: $description,
                currentUser: $currentUser,
                signalement: $intervention->getSignalement(),
            );

            $this->visiteNotifier->notifyUsagers($intervention, NotificationMailerType::TYPE_VISITE_EDITED_TO_USAGER);

            $this->visiteNotifier->notifyAgents(
                intervention: $intervention,
                suivi: $suivi,
                currentUser: $currentUser,
                notificationMailerType: null,
            );
        }
    }
}
