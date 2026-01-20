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

    public function onInterventionCanceled(TransitionEvent $event): void
    {
        /** @var Intervention $intervention */
        $intervention = $event->getSubject();
        /** @var User $currentUser */
        $currentUser = $this->security->getUser();
        $context = $event->getContext();
        if (InterventionType::VISITE === $intervention->getType()) {
            $description = 'Annulation de visite :';
            $description .= ' la visite du logement prévue le '.$intervention->getScheduledAt()->format('d/m/Y');
            $description .= ' a été annulée pour le motif suivant : <br>';
            $description .= $intervention->getDetails();
            $signalement = $intervention->getSignalement();
            $isLogementVacant = $signalement->getIsLogementVacant();
            $suivi = $this->suiviManager->createSuivi(
                signalement: $signalement,
                description: $description,
                type: Suivi::TYPE_AUTO,
                category: SuiviCategory::INTERVENTION_IS_CANCELED,
                partner: $context['createdByPartner'],
                user: $currentUser,
                isPublic: !$isLogementVacant,
                context: Suivi::CONTEXT_INTERVENTION,
            );

            if (!$isLogementVacant) {
                $this->visiteNotifier->notifyUsagers(
                    intervention: $intervention,
                    notificationMailerType: NotificationMailerType::TYPE_VISITE_CANCELED_TO_USAGER,
                    suivi: $suivi
                );
            }

            $this->visiteNotifier->notifyInAppSubscribers(
                intervention: $intervention,
                suivi: $suivi,
                currentUser: $currentUser,
            );
        }
    }
}
