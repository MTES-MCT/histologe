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
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Workflow\Event\Event;

class InterventionAbortedSubscriber implements EventSubscriberInterface
{
    public const NAME = 'workflow.intervention_planning.transition.abort';

    public function __construct(
        private Security $security,
        private VisiteNotifier $visiteNotifier,
        private SuiviManager $suiviManager,
        #[Autowire(env: 'FEATURE_NEW_DASHBOARD')]
        private readonly bool $featureNewDashboard,
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
        /** @var Intervention $intervention */
        $intervention = $event->getSubject();
        /** @var User $currentUser */
        $currentUser = $this->security->getUser();
        if (InterventionType::VISITE === $intervention->getType()) {
            $description = 'La visite du logement prévue le '.$intervention->getScheduledAt()->format('d/m/Y');
            $description .= ' n\'a pas pu avoir lieu pour le motif suivant :<br>';
            $description .= $intervention->getDetails();
            $suivi = $this->suiviManager->createSuivi(
                signalement: $intervention->getSignalement(),
                description: $description,
                type: Suivi::TYPE_AUTO,
                category: SuiviCategory::INTERVENTION_IS_ABORTED,
                isPublic: true,
                user: $currentUser,
                context: Suivi::CONTEXT_INTERVENTION,
            );

            $this->visiteNotifier->notifyUsagers(
                intervention: $intervention,
                notificationMailerType: NotificationMailerType::TYPE_VISITE_ABORTED_TO_USAGER,
                suivi: $suivi
            );

            if ($this->featureNewDashboard) {
                $this->visiteNotifier->notifySubscribers(
                    notificationMailerType: NotificationMailerType::TYPE_VISITE_ABORTED_TO_PARTNER,
                    intervention: $intervention,
                    suivi: $suivi,
                    currentUser: $currentUser,
                );
            } else {
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
}
