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
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

readonly class InterventionEditedSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private VisiteNotifier $visiteNotifier,
        private SuiviManager $suiviManager,
        private UrlGeneratorInterface $urlGenerator,
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
            $description = 'Edition de la conclusion de la visite par '.$partnerName.'.<br>';
            $description .= 'Commentaire opérateur :<br>';
            $description .= $intervention->getDetails();

            if (!$intervention->getFiles()->isEmpty()) {
                $description .= '<br>Rapport de visite : ';

                $urlDocument = $this->urlGenerator->generate(
                    'show_file',
                    ['uuid' => $intervention->getFiles()->first()->getUuid()],
                    UrlGeneratorInterface::ABSOLUTE_URL
                );

                $description .= '<a href="'.$urlDocument.'" title="Afficher le document" rel="noopener" target="_blank">Afficher le document</a>';
            }

            $suivi = $this->suiviManager->createSuivi(
                signalement: $intervention->getSignalement(),
                description: $description,
                type: Suivi::TYPE_AUTO,
                isPublic: $event->isUsagerNotified(),
                user: $currentUser,
                context: Suivi::CONTEXT_INTERVENTION,
                category: SuiviCategory::INTERVENTION_HAS_CONCLUSION_EDITED,
            );

            if ($event->isUsagerNotified()) {
                $this->visiteNotifier->notifyUsagers(
                    intervention: $intervention,
                    notificationMailerType: NotificationMailerType::TYPE_VISITE_EDITED_TO_USAGER,
                    suivi: $suivi
                );
            }

            $this->visiteNotifier->notifyAgents(
                intervention: $intervention,
                suivi: $suivi,
                currentUser: $currentUser,
                notificationMailerType: null,
            );
        }
    }
}
