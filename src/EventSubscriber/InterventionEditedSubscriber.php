<?php

namespace App\EventSubscriber;

use App\Entity\Enum\InterventionType;
use App\Entity\Enum\SuiviCategory;
use App\Entity\Suivi;
use App\Event\InterventionEditedEvent;
use App\Manager\SuiviManager;
use App\Service\Mailer\NotificationMailerType;
use App\Service\Signalement\VisiteNotifier;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

readonly class InterventionEditedSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private VisiteNotifier $visiteNotifier,
        private SuiviManager $suiviManager,
        #[Autowire(env: 'FEATURE_NEW_DASHBOARD')]
        private readonly bool $featureNewDashboard,
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
        if (InterventionType::VISITE === $intervention->getType()) {
            $currentUser = $event->getUser();
            $partnerName = $intervention->getPartner() ? $intervention->getPartner()->getNom() : 'Non renseigné';
            $description = 'Edition de la conclusion de la visite par '.$partnerName.'.<br>';
            $description .= 'Commentaire opérateur :<br>';
            $description .= $intervention->getDetails();

            $suivi = $this->suiviManager->createSuivi(
                signalement: $intervention->getSignalement(),
                description: $description,
                type: Suivi::TYPE_AUTO,
                category: SuiviCategory::INTERVENTION_HAS_CONCLUSION_EDITED,
                isPublic: $event->isUsagerNotified(),
                user: $currentUser,
                context: Suivi::CONTEXT_INTERVENTION,
                files: $intervention->getFiles(),
            );

            if ($event->isUsagerNotified()) {
                $this->visiteNotifier->notifyUsagers(
                    intervention: $intervention,
                    notificationMailerType: NotificationMailerType::TYPE_VISITE_EDITED_TO_USAGER,
                    suivi: $suivi
                );
            }

            if ($this->featureNewDashboard) {
                $this->visiteNotifier->NotifyInAppSubscribers(
                    intervention: $intervention,
                    suivi: $suivi,
                    currentUser: $currentUser,
                );
            } else {
                $this->visiteNotifier->notifyAgents(
                    intervention: $intervention,
                    suivi: $suivi,
                    currentUser: $currentUser,
                    notificationMailerType: null,
                );
            }
        }
    }
}
