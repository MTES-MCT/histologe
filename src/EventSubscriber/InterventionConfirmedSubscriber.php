<?php

namespace App\EventSubscriber;

use App\Entity\Enum\InterventionType;
use App\Entity\Enum\SuiviCategory;
use App\Entity\Intervention;
use App\Entity\Model\InformationProcedure;
use App\Entity\Signalement;
use App\Entity\Suivi;
use App\Entity\User;
use App\Manager\SignalementManager;
use App\Manager\SuiviManager;
use App\Service\Mailer\NotificationMailerType;
use App\Service\Signalement\VisiteNotifier;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Workflow\Event\TransitionEvent;

class InterventionConfirmedSubscriber implements EventSubscriberInterface
{
    public const string NAME = 'workflow.intervention_planning.transition.confirm';

    public function __construct(
        private readonly Security $security,
        private readonly VisiteNotifier $visiteNotifier,
        private readonly SuiviManager $suiviManager,
        private readonly SignalementManager $signalementManager,
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
        $context = $event->getContext();
        if (InterventionType::VISITE === $intervention->getType() && !isset($context['esabora'])) {
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

            $isUsagerNotified = $event->getContext()['isUsagerNotified'] ?? true;
            $suivi = $this->suiviManager->createSuivi(
                signalement: $intervention->getSignalement(),
                description: $description,
                type: Suivi::TYPE_AUTO,
                category: SuiviCategory::INTERVENTION_HAS_CONCLUSION,
                partner: $context['createdByPartner'],
                user: $currentUser,
                isPublic: $isUsagerNotified,
                context: Suivi::CONTEXT_INTERVENTION,
                files: $intervention->getFiles(),
            );

            if ($isUsagerNotified) {
                $this->visiteNotifier->notifyUsagers(
                    intervention: $intervention,
                    notificationMailerType: NotificationMailerType::TYPE_VISITE_CONFIRMED_TO_USAGER,
                    suivi: $suivi
                );
            }

            $this->visiteNotifier->notifySubscribers(
                notificationMailerType: NotificationMailerType::TYPE_VISITE_CONFIRMED_TO_PARTNER,
                intervention: $intervention,
                suivi: $suivi,
                currentUser: $currentUser,
            );
        }

        if (in_array($intervention->getType(), [InterventionType::VISITE_CONTROLE, InterventionType::VISITE], true)) {
            $this->setBailleurPrevenu($intervention->getSignalement());
        }
    }

    private function setBailleurPrevenu(Signalement $signalement): void
    {
        $informationProcedure = new InformationProcedure();
        if (!empty($signalement->getInformationProcedure())) {
            $informationProcedure = clone $signalement->getInformationProcedure();
        }
        $informationProcedure->setInfoProcedureBailleurPrevenu('oui');
        $signalement->setInformationProcedure($informationProcedure);
        $signalement->setIsProprioAverti(true);
        $this->signalementManager->save($signalement);
    }
}
