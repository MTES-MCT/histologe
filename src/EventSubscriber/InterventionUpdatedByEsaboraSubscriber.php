<?php

namespace App\EventSubscriber;

use App\Entity\Enum\InterventionType;
use App\Entity\Enum\SuiviCategory;
use App\Entity\Suivi;
use App\Event\InterventionUpdatedByEsaboraEvent;
use App\Manager\SuiviManager;
use App\Service\Intervention\InterventionDescriptionGenerator;
use App\Service\Mailer\NotificationMailerType;
use App\Service\Signalement\VisiteNotifier;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

readonly class InterventionUpdatedByEsaboraSubscriber implements EventSubscriberInterface
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
            InterventionUpdatedByEsaboraEvent::NAME => 'onInterventionEdited',
        ];
    }

    public function onInterventionEdited(InterventionUpdatedByEsaboraEvent $event): void
    {
        $intervention = $event->getIntervention();
        $description = (string) InterventionDescriptionGenerator::generate($intervention, InterventionUpdatedByEsaboraEvent::NAME);
        $suivi = $this->suiviManager->createSuivi(
            signalement: $intervention->getSignalement(),
            description: $description,
            type: Suivi::TYPE_AUTO,
            category: SuiviCategory::INTERVENTION_IS_RESCHEDULED,
            partner: $event->getPartner(),
            user: $event->getUser(),
            isPublic: true,
            context: Suivi::CONTEXT_INTERVENTION,
        );
        $event->setSuivi($suivi);
        if (InterventionType::VISITE === $intervention->getType()
            && $intervention->getScheduledAt()->format('Y-m-d') >= (new \DateTimeImmutable())->format('Y-m-d')
        ) {
            $this->visiteNotifier->notifyUsagers(
                intervention: $intervention,
                notificationMailerType: NotificationMailerType::TYPE_VISITE_RESCHEDULED_TO_USAGER,
                suivi: $suivi,
                previousDate: $intervention->getPreviousScheduledAt()
            );
        }

        if (InterventionType::ARRETE_PREFECTORAL === $intervention->getType()) {
            $this->visiteNotifier->notifyUsagers(
                intervention: $intervention,
                notificationMailerType: NotificationMailerType::TYPE_ARRETE_CREATED_TO_USAGER,
                suivi: $suivi,
            );
        }

        if ($this->featureNewDashboard) {
            $this->visiteNotifier->notifyInAppSubscribers(
                intervention: $intervention,
                suivi: $suivi,
                currentUser: $event->getUser(),
            );
        } else {
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
