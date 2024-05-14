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

class InterventionAbortedSubscriber implements EventSubscriberInterface
{
    public const NAME = 'workflow.intervention_planning.transition.abort';

    public function __construct(
        private Security $security,
        private VisiteNotifier $visiteNotifier,
        private SuiviManager $suiviManager,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            self::NAME => 'onInterventionAborted',
        ];
    }

    public function onInterventionAborted(Event $event): void
    {
        $intervention = $event->getSubject();
        $currentUser = $this->security->getUser();
        if (InterventionType::VISITE === $intervention->getType()) {
            $description = 'La visite du logement prévue le '.$intervention->getScheduledAt()->format('d/m/Y');
            $description .= ' n\'a pas pu avoir lieu pour le motif suivant :<br>';
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
            $this->suiviManager->save($suivi);

            $this->visiteNotifier->notifyUsagers($intervention, NotificationMailerType::TYPE_VISITE_ABORTED_TO_USAGER);

            $this->visiteNotifier->notifyAgents(
                intervention: $intervention,
                suivi: $suivi,
                currentUser: $currentUser,
                notificationMailerType: NotificationMailerType::TYPE_VISITE_ABORTED_TO_PARTNER,
                notifyOtherAffectedPartners: true,
            );
        }
    }
}
