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
            $description = sprintf(
                'Les informations sur la visite du logement effectuée le %s par %s ont été modifiées.<br><br> %s du logement %s : <br>%s.<br><br>Commentaire opérateur : %s',
                $intervention->getScheduledAtFormated(),
                $partnerName,
                count($intervention->getConcludeProcedure()) > 1 ? 'Les nouvelles situations observées' : 'La nouvelle situation observé',
                count($intervention->getConcludeProcedure()) > 1 ? 'sont' : 'est',
                $intervention->getConcludeProcedureString(),
                $intervention->getDetails()
            );

            $suivi = $this->suiviManager->createSuivi(
                signalement: $intervention->getSignalement(),
                description: $description,
                type: Suivi::TYPE_AUTO,
                category: SuiviCategory::INTERVENTION_HAS_CONCLUSION_EDITED,
                partner: $event->getPartner(),
                user: $currentUser,
                isPublic: $event->isUsagerNotified(),
                context: Suivi::CONTEXT_INTERVENTION,
                files: $intervention->getFiles(),
            );

            if ($event->isUsagerNotified()) {
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
