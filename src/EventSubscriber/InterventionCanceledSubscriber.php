<?php

namespace App\EventSubscriber;

use App\Entity\Enum\InterventionType;
use App\Entity\Enum\SuiviCategory;
use App\Entity\Intervention;
use App\Entity\Suivi;
use App\Entity\User;
use App\Manager\SuiviManager;
use App\Service\Mailer\NotificationMailerType;
use App\Service\Signalement\VisiteNotifier;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Workflow\Event\Event;

class InterventionCanceledSubscriber implements EventSubscriberInterface
{
    public const NAME = 'workflow.intervention_planning.transition.cancel';

    public function __construct(
        private Security $security,
        private VisiteNotifier $visiteNotifier,
        private SuiviManager $suiviManager,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            self::NAME => 'onInterventionCanceled',
        ];
    }

    public function onInterventionCanceled(Event $event): void
    {
        /** @var Intervention $intervention */
        $intervention = $event->getSubject();
        /** @var User $currentUser */
        $currentUser = $this->security->getUser();
        if (InterventionType::VISITE === $intervention->getType()) {
            $description = 'Annulation de visite :';
            $description .= ' la visite du logement prévue le '.$intervention->getScheduledAt()->format('d/m/Y');
            $description .= ' a été annulée pour le motif suivant : <br>';
            $description .= $intervention->getDetails();
            $suivi = $this->suiviManager->createSuivi(
                signalement: $intervention->getSignalement(),
                description: $description,
                type: Suivi::TYPE_AUTO,
                isPublic: true,
                user: $currentUser,
                context: Suivi::CONTEXT_INTERVENTION,
                category: SuiviCategory::INTERVENTION_IS_CANCELED,
            );

            $this->visiteNotifier->notifyUsagers(
                intervention: $intervention,
                notificationMailerType: NotificationMailerType::TYPE_VISITE_CANCELED_TO_USAGER,
                suivi: $suivi
            );

            $this->visiteNotifier->notifyAgents(
                intervention: $intervention,
                suivi: $suivi,
                currentUser: $currentUser,
                notificationMailerType: null,
                notifyOtherAffectedPartners: true,
            );
        }
    }
}
