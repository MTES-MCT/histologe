<?php

namespace App\EventSubscriber;

use App\Entity\Enum\InterventionType;
use App\Entity\Enum\SuiviCategory;
use App\Entity\Suivi;
use App\Event\InterventionCreatedEvent;
use App\Manager\SuiviManager;
use App\Service\Interconnection\Esabora\EsaboraSISHService;
use App\Service\Intervention\InterventionDescriptionGenerator;
use App\Service\Mailer\NotificationMailerType;
use App\Service\Signalement\VisiteNotifier;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

readonly class InterventionCreatedSubscriber implements EventSubscriberInterface
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
            InterventionCreatedEvent::NAME => 'onInterventionCreated',
        ];
    }

    public function onInterventionCreated(InterventionCreatedEvent $event): void
    {
        $intervention = $event->getIntervention();
        $signalement = $intervention->getSignalement();
        $description = (string) InterventionDescriptionGenerator::generate($intervention, InterventionCreatedEvent::NAME);
        $isPublic = true;
        if (EsaboraSISHService::NAME_SI === $event->getSource() && $signalement->isTiersDeclarant()) {
            $isPublic = false;
        }
        $suivi = $this->suiviManager->createSuivi(
            signalement: $intervention->getSignalement(),
            description: $description,
            type: Suivi::TYPE_AUTO,
            category : SuiviCategory::INTERVENTION_IS_CREATED,
            partner: $event->getPartner(),
            user: $event->getUser(),
            isPublic: $isPublic,
            context: Suivi::CONTEXT_INTERVENTION,
        );

        if (EsaboraSISHService::NAME_SI === $event->getSource()) {
            $suivi->setSource(EsaboraSISHService::NAME_SI);
        }

        $event->setSuivi($suivi);
        if (InterventionType::VISITE === $intervention->getType()
            && $intervention->getScheduledAt()->format('Y-m-d') >= (new \DateTimeImmutable())->format('Y-m-d')
        ) {
            $this->visiteNotifier->notifyUsagers(
                intervention: $intervention,
                notificationMailerType: NotificationMailerType::TYPE_VISITE_CREATED_TO_USAGER,
                suivi: $suivi,
            );
        }

        if (InterventionType::ARRETE_PREFECTORAL === $intervention->getType() && $suivi->getIsPublic()) {
            $this->visiteNotifier->notifyUsagers(
                intervention: $intervention,
                notificationMailerType: NotificationMailerType::TYPE_ARRETE_CREATED_TO_USAGER,
                suivi: $suivi
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
