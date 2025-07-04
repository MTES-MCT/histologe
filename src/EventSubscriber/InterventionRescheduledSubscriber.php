<?php

namespace App\EventSubscriber;

use App\Entity\Enum\InterventionType;
use App\Entity\Enum\SuiviCategory;
use App\Entity\Suivi;
use App\Event\InterventionRescheduledEvent;
use App\Manager\SuiviManager;
use App\Service\Mailer\NotificationMailerType;
use App\Service\Signalement\VisiteNotifier;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class InterventionRescheduledSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly VisiteNotifier $visiteNotifier,
        private readonly SuiviManager $suiviManager,
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
            $partnerName = $intervention->getPartner() ? $intervention->getPartner()->getNom() : 'Non renseigné';
            $description = 'Changement de date de visite : la visite du logement initialement prévue le ';
            $description .= $event->getPreviousDate()->format('d/m/Y');
            $description .= ' a été décalée au ';
            $description .= $intervention->getScheduledAt()->format('d/m/Y').'.';
            $description .= '<br>';
            $description .= 'La visite sera effectuée par '.$partnerName.'.';
            $suivi = $this->suiviManager->createSuivi(
                signalement: $intervention->getSignalement(),
                description: $description,
                type: Suivi::TYPE_AUTO,
                category: SuiviCategory::INTERVENTION_IS_RESCHEDULED,
                isPublic: true,
                user: $event->getUser(),
                context: Suivi::CONTEXT_INTERVENTION,
            );

            $this->visiteNotifier->notifyUsagers(
                intervention: $intervention,
                notificationMailerType: NotificationMailerType::TYPE_VISITE_RESCHEDULED_TO_USAGER,
                suivi: $suivi,
                previousDate: $event->getPreviousDate()
            );

            $this->visiteNotifier->notifyAgents(
                intervention: $intervention,
                suivi: $suivi,
                currentUser: $event->getUser(),
                notificationMailerType: null,
                notifyOtherAffectedPartners: true,
            );
        }
    }
}
