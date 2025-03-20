<?php

namespace App\EventSubscriber;

use App\Entity\Enum\InterventionType;
use App\Entity\Suivi;
use App\Event\InterventionCreatedEvent;
use App\Manager\SuiviManager;
use App\Service\Intervention\InterventionDescriptionGenerator;
use App\Service\Mailer\NotificationMailerType;
use App\Service\Signalement\VisiteNotifier;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerInterface;

readonly class InterventionCreatedSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private VisiteNotifier $visiteNotifier,
        private SuiviManager $suiviManager,
        #[Autowire(service: 'html_sanitizer.sanitizer.app.message_sanitizer')]
        private HtmlSanitizerInterface $htmlSanitizer,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            InterventionCreatedEvent::NAME => 'onInterventionCreated',
            InterventionCreatedEvent::UPDATED_BY_ESABORA => 'onInterventionEdited',
        ];
    }

    public function onInterventionCreated(InterventionCreatedEvent $event): void
    {
        $this->createSuiviAndNotify($event);
    }

    public function onInterventionEdited(InterventionCreatedEvent $event): void
    {
        $this->createSuiviAndNotify($event);
    }

    private function createSuiviAndNotify(InterventionCreatedEvent $event): void
    {
        $intervention = $event->getIntervention();
        $description = (string) InterventionDescriptionGenerator::generate($intervention, InterventionCreatedEvent::NAME);
        $foundSuivi = $this->suiviManager->findOneBy([
            'signalement' => $intervention->getSignalement(),
            'description' => $this->htmlSanitizer->sanitize($description),
        ]);

        $event->setSuivi($foundSuivi);
        if (!$foundSuivi) {
            $suivi = $this->suiviManager->createSuivi(
                signalement: $intervention->getSignalement(),
                description: $description,
                type: Suivi::TYPE_AUTO,
                isPublic: true,
                user: $event->getUser(),
                context: Suivi::CONTEXT_INTERVENTION,
            );
            $event->setSuivi($suivi);
            if (InterventionType::VISITE === $intervention->getType()
                && $intervention->getScheduledAt()->format('Y-m-d') >= (new \DateTimeImmutable())->format('Y-m-d')
            ) {
                $this->visiteNotifier->notifyUsagers(
                    $intervention,
                    NotificationMailerType::TYPE_VISITE_CREATED_TO_USAGER
                );
            }

            if (InterventionType::ARRETE_PREFECTORAL === $intervention->getType()) {
                $this->visiteNotifier->notifyUsagers(
                    $intervention,
                    NotificationMailerType::TYPE_ARRETE_CREATED_TO_USAGER
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
    }
}
