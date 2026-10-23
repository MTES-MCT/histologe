<?php

namespace App\EventSubscriber;

use App\Entity\Enum\InterventionType;
use App\Entity\Enum\SuiviCategory;
use App\Entity\Intervention;
use App\Event\InterventionUpdatedByEsaboraEvent;
use App\Manager\SuiviManager;
use App\Service\Interconnection\Esabora\EsaboraSISHService;
use App\Service\Mailer\NotificationMailerType;
use App\Service\Signalement\Suivi\SuiviDescriptionHelper;
use App\Service\Signalement\Suivi\SuiviDescriptionVariant;
use App\Service\Signalement\VisiteNotifier;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

readonly class InterventionUpdatedByEsaboraSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private VisiteNotifier $visiteNotifier,
        private SuiviManager $suiviManager,
        private SuiviDescriptionHelper $suiviDescriptionHelper,
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

        // Arrêté : la description du suivi reprend les détails de l'arrêté, générés par InterventionDescriptionGenerator
        if (InterventionType::ARRETE_PREFECTORAL === $intervention->getType()) {
            $description = (string) $intervention->getDetails();
        } else {
            // Pour l'instant le seul besoin remonté par SISH est celui de la modification de date
            // Mais l'opérateur pourrait aussi être modifié (que ce soit ARS ou un opérateur externe)
            $isInPast = new \DateTimeImmutable() > $intervention->getScheduledAt()
                && Intervention::STATUS_DONE === $intervention->getStatus();
            $timezone = $signalement->getTimezone() ?? 'Europe/Paris';
            $description = $this->suiviDescriptionHelper->buildDescriptionForCreation(
                $suiviCategory,
                [
                    'typeLabel' => $intervention->getType()->label(),
                    'nameSI' => EsaboraSISHService::NAME_SI,
                    'date' => $intervention->getScheduledAt()->setTimezone(new \DateTimeZone($timezone))->format('d/m/Y'),
                    'commentBeforeVisite' => !$isInPast ? $intervention->getCommentBeforeVisite() : null,
                ],
                SuiviDescriptionVariant::ESABORA,
            );
        }

        $suivi = $this->suiviManager->createSuivi(
            signalement: $intervention->getSignalement(),
            description: $description,
            category: $suiviCategory,
            partner: $event->getPartner(),
            user: $event->getUser(),
            isVisibleForUsager: !$signalement->isTiersDeclarant(),
            sendMail: false,
        );
        if (InterventionType::VISITE === $intervention->getType()
            && $intervention->getScheduledAt()->format('Y-m-d') >= (new \DateTimeImmutable())->format('Y-m-d')
            && $suivi->getIsVisibleForUsager()
        ) {
            $this->visiteNotifier->notifyUsagers(
                intervention: $intervention,
                notificationMailerType: NotificationMailerType::TYPE_VISITE_RESCHEDULED_TO_USAGER,
                previousDate: $intervention->getPreviousScheduledAt()
            );
        }

        if (InterventionType::ARRETE_PREFECTORAL === $intervention->getType() && $suivi->getIsVisibleForUsager()) {
            $this->visiteNotifier->notifyUsagers(
                intervention: $intervention,
                notificationMailerType: NotificationMailerType::TYPE_ARRETE_CREATED_TO_USAGER,
            );
        }

        $this->visiteNotifier->notifyInAppSubscribers(
            intervention: $intervention,
            suivi: $suivi,
            currentUser: $event->getUser(),
        );
    }
}
