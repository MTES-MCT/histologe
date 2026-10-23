<?php

namespace App\EventSubscriber;

use App\Entity\Enum\InterventionType;
use App\Entity\Enum\SuiviCategory;
use App\Entity\Intervention;
use App\Event\InterventionCreatedEvent;
use App\Manager\SuiviManager;
use App\Service\Interconnection\Esabora\EsaboraSISHService;
use App\Service\Mailer\NotificationMailerType;
use App\Service\Signalement\Suivi\SuiviDescriptionHelper;
use App\Service\Signalement\VisiteNotifier;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

readonly class InterventionCreatedSubscriber implements EventSubscriberInterface
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
            InterventionCreatedEvent::NAME => 'onInterventionCreated',
        ];
    }

    public function onInterventionCreated(InterventionCreatedEvent $event): void
    {
        $intervention = $event->getIntervention();
        $signalement = $intervention->getSignalement();
        $isVisibleForUsager = true;
        if (EsaboraSISHService::NAME_SI === $event->getSource() && $signalement->isTiersDeclarant()) {
            $isVisibleForUsager = false;
        }

        $today = new \DateTimeImmutable();
        $isInPast = $today > $intervention->getScheduledAt()
            && Intervention::STATUS_DONE === $intervention->getStatus();

        switch ($intervention->getType()) {
            case InterventionType::VISITE:
                $suiviCategory = $isInPast ? SuiviCategory::INTERVENTION_IS_DONE : SuiviCategory::INTERVENTION_IS_CREATED;
                break;
            case InterventionType::VISITE_CONTROLE:
                $suiviCategory = $isInPast ? SuiviCategory::INTERVENTION_CONTROLE_IS_DONE : SuiviCategory::INTERVENTION_CONTROLE_IS_CREATED;
                break;
            case InterventionType::ARRETE_PREFECTORAL:
                $suiviCategory = SuiviCategory::INTERVENTION_ARRETE_IS_CREATED;
                break;
            default:
                $suiviCategory = SuiviCategory::INTERVENTION_IS_CREATED;
        }

        // Arrêté : la description du suivi reprend les détails de l'arrêté, générés par InterventionDescriptionGenerator
        if (InterventionType::ARRETE_PREFECTORAL === $intervention->getType()) {
            $description = (string) $intervention->getDetails();
        } else {
            $description = $this->suiviDescriptionHelper->buildDescriptionForCreation($suiviCategory, [
                'typeLabel' => $intervention->getType()->label(),
                'isInPast' => $isInPast,
                'address' => $signalement->getAddress()->getHousenumberAndStreet(),
                'date' => $intervention->getScheduledAtFormated(),
                'partnerName' => $intervention->getExternalOperator() ?? $intervention->getPartner()?->getNom() ?? 'Non renseigné',
                'commentBeforeVisite' => !$isInPast ? $intervention->getCommentBeforeVisite() : null,
            ]);
        }

        $suivi = $this->suiviManager->createSuivi(
            signalement: $intervention->getSignalement(),
            description: $description,
            category : $suiviCategory,
            partner: $event->getPartner(),
            user: $event->getUser(),
            isVisibleForUsager: $isVisibleForUsager,
            sendMail: false,
        );
        $event->setSuivi($suivi);
        if (InterventionType::VISITE === $intervention->getType()
            && $intervention->getScheduledAt()->format('Y-m-d') >= (new \DateTimeImmutable())->format('Y-m-d')
        ) {
            $this->visiteNotifier->notifyUsagers(
                intervention: $intervention,
                notificationMailerType: NotificationMailerType::TYPE_VISITE_CREATED_TO_USAGER,
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
