<?php

namespace App\EventSubscriber;

use App\Entity\Enum\InterventionType;
use App\Entity\Suivi;
use App\Manager\SuiviManager;
use App\Service\Mailer\NotificationMailerType;
use App\Service\Signalement\VisiteNotifier;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Workflow\Event\Event;

class InterventionConfirmedSubscriber implements EventSubscriberInterface
{
    public const NAME = 'workflow.intervention_planning.transition.confirm';

    public function __construct(
        private Security $security,
        private VisiteNotifier $visiteNotifier,
        private SuiviManager $suiviManager,
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
            $description = 'Après visite du logement';
            if ($intervention->getPartner()) {
                $description .= ' par '.$intervention->getPartner()->getNom();
            }
            $description .= ', la situation observée du logement est :<br>';
            foreach ($intervention->getConcludeProcedure() as $concludeProcedure) {
                $description .= '- '.$concludeProcedure->label().'<br>';
            }
            $description .= '<br>Commentaire opérateur :<br>';
            $description .= $intervention->getDetails();
            $isUsagerNotified = $event->getContext()['isUsagerNotified'] ?? true;
            $suivi = $this->suiviManager->createSuivi(
                user: $currentUser,
                signalement: $intervention->getSignalement(),
                isPublic: $isUsagerNotified,
                context: Suivi::CONTEXT_INTERVENTION,
                params: [
                    'description' => $description,
                    'type' => Suivi::TYPE_AUTO,
                ],
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
