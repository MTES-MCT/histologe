<?php

namespace App\EventSubscriber;

use App\Entity\Enum\InterventionType;
use App\Entity\Suivi;
use App\Manager\SuiviManager;
use App\Service\Mailer\NotificationMailerType;
use App\Service\Signalement\VisiteNotifier;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Workflow\Event\Event;

class InterventionCanceledSubscriber implements EventSubscriberInterface
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
            'workflow.intervention_planning.transition.cancel' => 'onInterventionCanceled',
        ];
    }

    public function onInterventionCanceled(Event $event): void
    {
        $intervention = $event->getSubject();
        $currentUser = $this->security->getUser();
        if (InterventionType::VISITE === $intervention->getType()) {
            $description = 'Annulation de visite :';
            $description .= ' la visite du logement prévue le '.$intervention->getDate()->format('d/m/Y');
            $description .= ' a été annulée pour le motif suivant : <br>';
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

            $this->visiteNotifier->notifyUsagers($intervention, NotificationMailerType::TYPE_VISITE_CANCELED_TO_USAGER);

            $this->visiteNotifier->notifyAgents(
                intervention: $intervention,
                suivi: $suivi,
                currentUser: $currentUser,
                notificationMailerType: null,
            );
        }
    }
}
