<?php

namespace App\EventSubscriber;

use App\Entity\Enum\InterventionType;
use App\Entity\Suivi;
use App\Event\InterventionEditedEvent;
use App\Manager\SuiviManager;
use App\Service\Mailer\NotificationMailerType;
use App\Service\Signalement\VisiteNotifier;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class InterventionEditedSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly VisiteNotifier $visiteNotifier,
        private readonly SuiviManager $suiviManager,
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

            if (!empty($intervention->getFiles())) {
                $description .= '<br>Rapport de visite : ';

                $urlDocument = $this->urlGenerator->generate(
                    'show_uploaded_file',
                    ['filename' => $intervention->getFiles()->first()->getFilename()],
                    UrlGeneratorInterface::ABSOLUTE_URL
                ).'/'.$intervention->getSignalement()->getUuid();

                $description .= '<a href="'.$urlDocument.'" title="Afficher le document" rel="noopener" target="_blank">Afficher le document</a>';
            }

            $suivi = $this->suiviManager->createSuivi(
                user: $currentUser,
                signalement: $intervention->getSignalement(),
                params: [
                    'description' => $description,
                    'type' => Suivi::TYPE_AUTO,
                ],
                isPublic: $event->isUsagerNotified(),
                context: Suivi::CONTEXT_INTERVENTION,
            );
            $this->suiviManager->save($suivi);

            if ($event->isUsagerNotified()) {
                $this->visiteNotifier->notifyUsagers(
                    $intervention,
                    NotificationMailerType::TYPE_VISITE_EDITED_TO_USAGER
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
