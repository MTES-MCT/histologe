<?php

namespace App\EventSubscriber;

use App\Entity\Enum\InterventionType;
use App\Entity\Enum\SuiviCategory;
use App\Entity\Intervention;
use App\Entity\Suivi;
use App\Entity\User;
use App\Manager\SuiviManager;
use App\Service\Mailer\NotificationMailerType;
use App\Service\Signalement\VisiteNotifier;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Workflow\Event\TransitionEvent;

class InterventionConfirmedSubscriber implements EventSubscriberInterface
{
    public const string NAME = 'workflow.intervention_planning.transition.confirm';

    public function __construct(
        private readonly Security $security,
        private readonly VisiteNotifier $visiteNotifier,
        private readonly SuiviManager $suiviManager,
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            self::NAME => 'onInterventionConfirmed',
        ];
    }

    public function onInterventionConfirmed(TransitionEvent $event): void
    {
        /** @var Intervention $intervention */
        $intervention = $event->getSubject();
        /** @var User $currentUser */
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

            if (!$intervention->getFiles()->isEmpty()) {
                $description .= '<br>Rapport de visite : ';

                $urlDocument = $this->urlGenerator->generate(
                    'show_file',
                    ['uuid' => $intervention->getFiles()->first()->getUuid()],
                    UrlGeneratorInterface::ABSOLUTE_URL
                );

                $description .= '<a href="'.$urlDocument.'" title="Afficher le document" rel="noopener" target="_blank">Afficher le document</a>';
            }

            $isUsagerNotified = $event->getContext()['isUsagerNotified'] ?? true;
            $suivi = $this->suiviManager->createSuivi(
                signalement: $intervention->getSignalement(),
                description: $description,
                type: Suivi::TYPE_AUTO,
                category: SuiviCategory::INTERVENTION_HAS_CONCLUSION,
                isPublic: $isUsagerNotified,
                user: $currentUser,
                context: Suivi::CONTEXT_INTERVENTION,
            );

            if ($isUsagerNotified) {
                $this->visiteNotifier->notifyUsagers(
                    intervention: $intervention,
                    notificationMailerType: NotificationMailerType::TYPE_VISITE_CONFIRMED_TO_USAGER,
                    suivi: $suivi
                );
            }

            $this->visiteNotifier->notifyAgents(
                intervention: $intervention,
                suivi: $suivi,
                currentUser: $currentUser,
                notificationMailerType: NotificationMailerType::TYPE_VISITE_CONFIRMED_TO_PARTNER,
                notifyOtherAffectedPartners: true,
            );
        }
    }
}
