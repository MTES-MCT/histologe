<?php

namespace App\EventSubscriber;

use App\Entity\Enum\InterventionType;
use App\Entity\Intervention;
use App\Entity\Suivi;
use App\Event\InterventionCreatedEvent;
use App\Manager\SuiviManager;
use App\Service\Intervention\InterventionDescriptionGenerator;
use App\Service\Mailer\NotificationMailerType;
use App\Service\Signalement\VisiteNotifier;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class InterventionCreatedSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly VisiteNotifier $visiteNotifier,
        private readonly SuiviManager $suiviManager,
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
        $suivi = $this->suiviManager->createSuivi(
            user: $event->getUser(),
            signalement: $intervention->getSignalement(),
            params: $this->getParams($intervention),
            isPublic: true,
            context: Suivi::CONTEXT_INTERVENTION,
        );
        $this->suiviManager->save($suivi);

        if (InterventionType::VISITE === $intervention->getType() && $intervention->getScheduledAt() >= new \DateTimeImmutable()) {
            $this->visiteNotifier->notifyUsagers(
                $intervention,
                NotificationMailerType::TYPE_VISITE_CREATED_TO_USAGER
            );
        }

        $this->visiteNotifier->notifyAgents(
            intervention: $intervention,
            suivi: $suivi,
            currentUser: $event->getUser(),
            notificationMailerType: null,
            notifyOtherAffectedPartners: true,
        );
    }

    private function getParams(Intervention $intervention): array
    {
        return [
            'type' => Suivi::TYPE_AUTO,
            'description' => InterventionDescriptionGenerator::generate(
                $intervention,
                InterventionCreatedEvent::NAME
            ),
        ];
    }
}
