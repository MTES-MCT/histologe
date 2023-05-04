<?php

namespace App\EventSubscriber;

use App\Entity\Enum\InterventionType;
use App\Entity\Suivi;
use App\Event\InterventionRescheduledEvent;
use App\Manager\SuiviManager;
use App\Service\Mailer\NotificationMailerType;
use App\Service\Signalement\VisiteNotifier;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class InterventionRescheduledSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private VisiteNotifier $visiteNotifier,
        private SuiviManager $suiviManager,
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
        if (InterventionType::VISITE === $intervention->getType()) {
            $description = 'Changement de date de visite : la visite du logement initialement prévue le ';
            $description .= $event->getPreviousDate()->format('d/m/Y');
            $description .= ' a été décalée au ';
            $description .= $intervention->getDate()->format('d/m/Y').'.';
            $description .= '<br>';
            $description .= 'La visite sera effectuée par '.$intervention->getPartner()->getNom().'.';
            $suivi = $this->suiviManager->createSuivi(
                user: $event->getUser(),
                signalement: $intervention->getSignalement(),
                isPublic: true,
                context: Suivi::CONTEXT_INTERVENTION,
                params: [
                    'description' => $description,
                    'type' => Suivi::TYPE_AUTO,
                ],
            );
            $this->suiviManager->save($suivi);

            $this->visiteNotifier->notifyUsagers(
                intervention: $intervention,
                notificationMailerType: NotificationMailerType::TYPE_VISITE_RESCHEDULED_TO_USAGER,
                previousDate: $event->getPreviousDate(),
            );

            $this->visiteNotifier->notifyAgents(
                intervention: $intervention,
                suivi: $suivi,
                currentUser: $event->getUser(),
                notificationMailerType: null,
            );
        }
    }
}
