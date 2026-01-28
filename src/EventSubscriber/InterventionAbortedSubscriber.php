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
use Symfony\Component\Workflow\Event\TransitionEvent;

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

    public function onInterventionAborted(TransitionEvent $event): void
    {
        /** @var Intervention $intervention */
        $intervention = $event->getSubject();
        /** @var User $currentUser */
        $currentUser = $this->security->getUser();
        $context = $event->getContext();
        if (InterventionType::VISITE === $intervention->getType()) {
            $description = 'La visite du logement prévue le '.$intervention->getScheduledAt()->format('d/m/Y');
            $description .= ' n\'a pas pu avoir lieu pour le motif suivant :<br>';
            $description .= $intervention->getDetails();
            $signalement = $intervention->getSignalement();
            $isLogementVacant = $signalement->getIsLogementVacant();
            $suivi = $this->suiviManager->createSuivi(
                signalement: $signalement,
                description: $description,
                type: Suivi::TYPE_AUTO,
                category: SuiviCategory::INTERVENTION_IS_ABORTED,
                partner: $context['createdByPartner'],
                user: $currentUser,
                isPublic: !$isLogementVacant,
                context: Suivi::CONTEXT_INTERVENTION,
            );

            $this->visiteNotifier->notifyUsagers(
                intervention: $intervention,
                notificationMailerType: NotificationMailerType::TYPE_VISITE_ABORTED_TO_USAGER,
                suivi: $suivi
            );

            $this->visiteNotifier->notifySubscribers(
                notificationMailerType: NotificationMailerType::TYPE_VISITE_ABORTED_TO_PARTNER,
                intervention: $intervention,
                suivi: $suivi,
                currentUser: $currentUser,
            );
        }
    }
}
