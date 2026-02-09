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
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

readonly class InterventionUpdatedByEsaboraSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private VisiteNotifier $visiteNotifier,
        private SuiviManager $suiviManager,
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
        $signalement = $intervention->getSignalement();
        $description = (string) InterventionDescriptionGenerator::generate($intervention, InterventionUpdatedByEsaboraEvent::NAME);

        switch ($intervention->getType()) {
            case InterventionType::VISITE:
                $suiviCategory = SuiviCategory::INTERVENTION_IS_RESCHEDULED;
                break;
            case InterventionType::VISITE_CONTROLE:
                $suiviCategory = SuiviCategory::INTERVENTION_CONTROLE_IS_RESCHEDULED;
                break;
            case InterventionType::ARRETE_PREFECTORAL:
                $suiviCategory = SuiviCategory::INTERVENTION_ARRETE_IS_RESCHEDULED;
                break;
            default:
                $suiviCategory = SuiviCategory::INTERVENTION_IS_RESCHEDULED;
        }

        $suivi = $this->suiviManager->createSuivi(
            signalement: $intervention->getSignalement(),
            description: $description,
            type: Suivi::TYPE_AUTO,
            category: $suiviCategory,
            partner: $event->getPartner(),
            user: $event->getUser(),
            isPublic: !$signalement->isTiersDeclarant(),
            context: Suivi::CONTEXT_INTERVENTION,
        );
        $event->setSuivi($suivi);
        if (InterventionType::VISITE === $intervention->getType()
            && $intervention->getScheduledAt()->format('Y-m-d') >= (new \DateTimeImmutable())->format('Y-m-d')
            && $suivi->getIsPublic()
        ) {
            $this->visiteNotifier->notifyUsagers(
                intervention: $intervention,
                notificationMailerType: NotificationMailerType::TYPE_VISITE_RESCHEDULED_TO_USAGER,
                suivi: $suivi,
                previousDate: $intervention->getPreviousScheduledAt()
            );
        }

        if (InterventionType::ARRETE_PREFECTORAL === $intervention->getType() && $suivi->getIsPublic()) {
            $this->visiteNotifier->notifyUsagers(
                intervention: $intervention,
                notificationMailerType: NotificationMailerType::TYPE_ARRETE_CREATED_TO_USAGER,
                suivi: $suivi,
            );
        }

        $this->visiteNotifier->notifyInAppSubscribers(
            intervention: $intervention,
            suivi: $suivi,
            currentUser: $event->getUser(),
        );
    }
}
