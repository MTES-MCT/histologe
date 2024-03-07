<?php

namespace App\EventSubscriber;

use App\Entity\Enum\InterventionType;
use App\Entity\Suivi;
use App\Manager\SuiviManager;
use App\Service\Mailer\NotificationMailerType;
use App\Service\Signalement\VisiteNotifier;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Workflow\Event\Event;

class InterventionConfirmedSubscriber implements EventSubscriberInterface
{
    public const NAME = 'workflow.intervention_planning.transition.confirm';

    public function __construct(
        private Security $security,
        private VisiteNotifier $visiteNotifier,
        private SuiviManager $suiviManager,
        private UrlGeneratorInterface $urlGenerator,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            self::NAME => 'onInterventionConfirmed',
        ];
    }

    public function onInterventionConfirmed(Event $event): void
    {
        $intervention = $event->getSubject();
        $currentUser = $this->security->getUser();
        if (InterventionType::VISITE === $intervention->getType()) {
            $partnerName = $intervention->getPartner() ? $intervention->getPartner()->getNom() : 'Non renseigné';
            $description = 'Après visite du logement';
            if ($intervention->getPartner()) {
                $description .= ' par '.$partnerName;
            }
            $description .= ', la situation observée du logement est :<br>';
            foreach ($intervention->getConcludeProcedure() as $concludeProcedure) {
                $description .= '- '.$concludeProcedure->label().'<br>';
            }
            $description .= '<br>Commentaire opérateur :<br>';
            $description .= $intervention->getDetails();

            if (!empty($intervention->getFiles())) {
                $description .= '<br>Rapport de visite : ';

                $urlDocument = $this->urlGenerator->generate(
                    'show_uploaded_file',
                    ['filename' => $intervention->getFiles()->first()->getFilename()],
                    UrlGeneratorInterface::ABSOLUTE_URL
                ).'?t=___TOKEN___';

                $description .= '<a href="'.$urlDocument.'" title="Afficher le document" rel="noopener" target="_blank">Afficher le document</a>';
            }

            $isUsagerNotified = $event->getContext()['isUsagerNotified'] ?? true;
            $suivi = $this->suiviManager->createSuivi(
                user: $currentUser,
                signalement: $intervention->getSignalement(),
                params: [
                    'description' => $description,
                    'type' => Suivi::TYPE_AUTO,
                ],
                isPublic: $isUsagerNotified,
                context: Suivi::CONTEXT_INTERVENTION,
            );
            $this->suiviManager->save($suivi);

            if ($isUsagerNotified) {
                $this->visiteNotifier->notifyUsagers($intervention, NotificationMailerType::TYPE_VISITE_CONFIRMED_TO_USAGER);
            }

            $this->visiteNotifier->notifyAgents(
                intervention: $intervention,
                suivi: $suivi,
                currentUser: $currentUser,
                notificationMailerType: NotificationMailerType::TYPE_VISITE_CONFIRMED_TO_PARTNER,
            );
        }
    }
}
