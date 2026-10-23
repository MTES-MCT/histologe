<?php

namespace App\EventSubscriber;

use App\Entity\Enum\InterventionType;
use App\Entity\Enum\SuiviCategory;
use App\Entity\Intervention;
use App\Entity\User;
use App\Manager\SuiviManager;
use App\Service\Mailer\NotificationMailerType;
use App\Service\Signalement\Suivi\SuiviDescriptionHelper;
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
        private SuiviDescriptionHelper $suiviDescriptionHelper,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            self::NAME => 'onInterventionAborted',
        ];
    }

    /**
     * @param TransitionEvent<Intervention> $event
     */
    public function onInterventionAborted(TransitionEvent $event): void
    {
        /** @var Intervention $intervention */
        $intervention = $event->getSubject();
        /** @var User $currentUser */
        $currentUser = $this->security->getUser();
        $context = $event->getContext();
        if (InterventionType::VISITE === $intervention->getType()) {
            $category = SuiviCategory::INTERVENTION_IS_ABORTED;
            $suivi = $this->suiviManager->createSuivi(
                signalement: $intervention->getSignalement(),
                description: $this->suiviDescriptionHelper->buildDescriptionForCreation($category, [
                    'date' => $intervention->getScheduledAt()->format('d/m/Y'),
                    'details' => $intervention->getDetails(),
                ]),
                category: $category,
                partner: $context['createdByPartner'],
                user: $currentUser,
                isVisibleForUsager: true,
                sendMail: false,
            );

            $this->visiteNotifier->notifyUsagers(
                intervention: $intervention,
                notificationMailerType: NotificationMailerType::TYPE_VISITE_ABORTED_TO_USAGER,
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
