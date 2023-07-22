<?php

namespace App\EventSubscriber;

use App\Entity\Enum\InterventionType;
use App\Entity\Intervention;
use App\Entity\Suivi;
use App\Event\InterventionCreatedEvent;
use App\Manager\SuiviManager;
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
            params: [
                'description' => $this->buildDescription($intervention),
                'type' => Suivi::TYPE_AUTO,
            ],
            isPublic: true,
            context: Suivi::CONTEXT_INTERVENTION,
        );
        $this->suiviManager->save($suivi);

        $this->visiteNotifier->notifyUsagers($intervention, NotificationMailerType::TYPE_VISITE_CREATED_TO_USAGER);

        $this->visiteNotifier->notifyAgents(
            intervention: $intervention,
            suivi: $suivi,
            currentUser: $event->getUser(),
            notificationMailerType: null,
        );
    }

    private function buildDescription(Intervention $intervention): string
    {
        if (InterventionType::ARRETE_PREFECTORAL === $intervention->getType()) {
            return $intervention->getDetails();
        }
        $labelVisite = strtolower($intervention->getType()->label());
        $partnerName = $intervention->getPartner() ? $intervention->getPartner()->getNom() : 'Non renseigné';

        return sprintf(
            '%s programmée : une %s du logement situé %s est prévue le %s.<br>La %s sera effectuée par %s.',
            ucfirst($labelVisite),
            $labelVisite,
            $intervention->getSignalement()->getAdresseOccupant(),
            $intervention->getScheduledAt()->format('d/m/Y'),
            $labelVisite,
            $partnerName
        );
    }
}
