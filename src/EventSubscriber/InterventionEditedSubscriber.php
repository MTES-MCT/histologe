<?php

namespace App\EventSubscriber;

use App\Entity\Enum\InterventionType;
use App\Entity\Enum\SuiviCategory;
use App\Entity\Suivi;
use App\Event\InterventionEditedEvent;
use App\Manager\SuiviManager;
use App\Service\Mailer\NotificationMailerType;
use App\Service\Signalement\VisiteNotifier;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

readonly class InterventionEditedSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private VisiteNotifier $visiteNotifier,
        private SuiviManager $suiviManager,
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
            $partnerName = $intervention->getPartner() ? $intervention->getPartner()->getNom() : 'Non renseigné';
            if (empty($intervention->getChangesForMail())) {
                $description = 'Edition de la conclusion de la visite par '.$partnerName.'.<br>';
                $description .= 'Commentaire opérateur :<br>';
                $description .= $intervention->getDetails();
            } else {
                $description = sprintf(
                    'Les informations sur la visite du logement effectuée le %s par %s ont été modifiées.',
                    $intervention->getScheduledAtFormated(),
                    $partnerName
                );
                /** @var array<string, array{old?: string, new?: string}> $changes */
                $changes = $intervention->getChangesForMail();
                if (isset($changes['concludeProcedure']['new'])) {
                    $description .= count($intervention->getConcludeProcedure()) > 1
                        ? '<br><br>Les nouvelles situations observées du logement sont : <br>' :
                        '<br><br>La nouvelle situation observeé du logement est : <br>';

                    $description .= $intervention->getConcludeProcedureString().'.';
                }

                if (isset($changes['details']['new'])) {
                    $description .= sprintf('<br><br>Commentaire opérateur : %s', $intervention->getDetails());
                }
            }

            $signalement = $intervention->getSignalement();
            $isLogementVacant = $signalement->getIsLogementVacant();
            $isUsagerNotified = $event->isUsagerNotified() && !$isLogementVacant;
            $suivi = $this->suiviManager->createSuivi(
                signalement: $signalement,
                description: $description,
                type: Suivi::TYPE_AUTO,
                category: SuiviCategory::INTERVENTION_HAS_CONCLUSION_EDITED,
                partner: $event->getPartner(),
                user: $currentUser,
                isPublic: $isUsagerNotified,
                context: Suivi::CONTEXT_INTERVENTION,
                files: $intervention->getFiles(),
            );

            if ($isUsagerNotified) {
                $this->visiteNotifier->notifyUsagers(
                    intervention: $intervention,
                    notificationMailerType: NotificationMailerType::TYPE_VISITE_EDITED_TO_USAGER,
                    suivi: $suivi
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
