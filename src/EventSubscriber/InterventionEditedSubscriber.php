<?php

namespace App\EventSubscriber;

use App\Entity\Enum\InterventionType;
use App\Entity\Suivi;
use App\Event\InterventionEditedEvent;
use App\Manager\SuiviManager;
use App\Service\Mailer\NotificationMailerType;
use App\Service\Signalement\VisiteNotifier;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class InterventionEditedSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private Security $security,
        private VisiteNotifier $visiteNotifier,
        private SuiviManager $suiviManager,
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
            $suivi = $this->suiviManager->createSuivi(
                user: $currentUser,
                signalement: $intervention->getSignalement(),
                isPublic: true,
                context: Suivi::CONTEXT_INTERVENTION,
                params: [
                    'description' => $description,
                    'type' => Suivi::TYPE_AUTO,
                ],
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
