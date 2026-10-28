<?php

namespace App\EventSubscriber;

use App\Entity\Enum\InterventionType;
use App\Entity\Enum\ProcedureType;
use App\Entity\Enum\SuiviCategory;
use App\Event\InterventionEditedEvent;
use App\Manager\SuiviManager;
use App\Service\Mailer\NotificationMailerType;
use App\Service\Signalement\Suivi\SuiviDescriptionHelper;
use App\Service\Signalement\VisiteNotifier;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

readonly class InterventionEditedSubscriber implements EventSubscriberInterface
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
            InterventionEditedEvent::NAME => 'onInterventionEdited',
        ];
    }

    public function onInterventionEdited(InterventionEditedEvent $event): void
    {
        $intervention = $event->getIntervention();
        if (InterventionType::VISITE === $intervention->getType()) {
            $currentUser = $event->getUser();
            /** @var array<string, array{old?: string, new?: string}> $changes */
            $changes = $intervention->getChangesForMail();
            $category = SuiviCategory::INTERVENTION_HAS_CONCLUSION_EDITED;
            $isVisibleForUsager = $intervention->getNotifyUsager() ?? false;
            $suivi = $this->suiviManager->createSuivi(
                signalement: $intervention->getSignalement(),
                description: $this->suiviDescriptionHelper->buildDescriptionForCreation($category, [
                    'hasChanges' => !empty($changes),
                    'date' => $intervention->getScheduledAtFormated(),
                    'partnerName' => $intervention->getPartner()?->getNom() ?? 'Non renseigné',
                    'concludeProcedureChanged' => isset($changes['concludeProcedure']['new']),
                    'concludeProcedures' => array_map(
                        static fn (ProcedureType $procedure) => $procedure->label(),
                        $intervention->getConcludeProcedure() ?? []
                    ),
                    'detailsChanged' => isset($changes['details']['new']),
                    'details' => $intervention->getDetails(),
                ]),
                category: $category,
                partner: $event->getPartner(),
                user: $currentUser,
                isVisibleForUsager: $isVisibleForUsager,
                sendMail: false,
                files: $intervention->getFiles(),
            );

            if ($isVisibleForUsager) {
                $this->visiteNotifier->notifyUsagers(
                    intervention: $intervention,
                    notificationMailerType: NotificationMailerType::TYPE_VISITE_EDITED_TO_USAGER,
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
