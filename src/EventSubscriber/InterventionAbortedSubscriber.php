<?php

namespace App\EventSubscriber;

use App\Entity\Enum\InterventionType;
use App\Service\Mailer\NotificationMailerType;
use App\Service\Signalement\VisiteNotifier;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Workflow\Event\Event;

class InterventionAbortedSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private Security $security,
        private VisiteNotifier $visiteNotifier,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'workflow.intervention_planning.transition.abort' => 'onInterventionAborted',
        ];
    }

    public function onInterventionAborted(Event $event): void
    {
        $intervention = $event->getSubject();
        $currentUser = $this->security->getUser();
        if (InterventionType::VISITE == $intervention->getType()) {
            $description = 'La visite du logement prévue le '.$intervention->getDate()->format('d/m/Y');
            $description .= ' n\'a pas pu avoir lieu pour le motif suivant :<br>';
            $description .= $intervention->getDetails();

            $suivi = $this->visiteNotifier->createSuivi(
                description: $description,
                currentUser: $currentUser,
                signalement: $intervention->getSignalement(),
            );

            // Send mails to usager
            $this->visiteNotifier->notifyUsagers($intervention, NotificationMailerType::TYPE_VISITE_ABORTED_TO_USAGER);

            // Send notifications to agents
            $this->visiteNotifier->notifyAgents(
                intervention: $intervention,
                suivi: $suivi,
                currentUser: $currentUser,
                notificationMailerType: NotificationMailerType::TYPE_VISITE_ABORTED_TO_PARTNER,
            );
        }
    }
}
