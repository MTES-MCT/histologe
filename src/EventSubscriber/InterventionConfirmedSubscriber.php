<?php

namespace App\EventSubscriber;

use App\Entity\Enum\InterventionType;
use App\Entity\Enum\ProcedureType;
use App\Entity\Enum\SuiviCategory;
use App\Entity\Intervention;
use App\Entity\Signalement;
use App\Entity\User;
use App\Manager\SuiviManager;
use App\Service\Mailer\NotificationMailerType;
use App\Service\Signalement\Suivi\SuiviDescriptionHelper;
use App\Service\Signalement\VisiteNotifier;
use Doctrine\ORM\EntityManagerInterface;
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
        private readonly EntityManagerInterface $entityManager,
        private readonly SuiviDescriptionHelper $suiviDescriptionHelper,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            self::NAME => 'onInterventionConfirmed',
        ];
    }

    /**
     * @param TransitionEvent<Intervention> $event
     */
    public function onInterventionConfirmed(TransitionEvent $event): void
    {
        /** @var Intervention $intervention */
        $intervention = $event->getSubject();
        /** @var User $currentUser */
        $currentUser = $this->security->getUser();
        $context = $event->getContext();
        if (InterventionType::VISITE === $intervention->getType() && !isset($context['esabora'])) {
            $category = SuiviCategory::INTERVENTION_HAS_CONCLUSION;
            $isVisibleForUsager = $intervention->getNotifyUsager() ?? false;
            $suivi = $this->suiviManager->createSuivi(
                signalement: $intervention->getSignalement(),
                description: $this->suiviDescriptionHelper->buildDescriptionForCreation($category, [
                    'partnerName' => $intervention->getPartner()?->getNom(),
                    'concludeProcedures' => array_map(
                        static fn (ProcedureType $procedure) => $procedure->label(),
                        $intervention->getConcludeProcedure() ?? []
                    ),
                    'details' => $intervention->getDetails(),
                ]),
                category: $category,
                partner: $context['createdByPartner'],
                user: $currentUser,
                isVisibleForUsager: $isVisibleForUsager,
                sendMail: false,
                files: $intervention->getFiles(),
            );

            if ($isVisibleForUsager) {
                $this->visiteNotifier->notifyUsagers(
                    intervention: $intervention,
                    notificationMailerType: NotificationMailerType::TYPE_VISITE_CONFIRMED_TO_USAGER,
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
        $signalement->setIsProprioAverti(true);
        $this->entityManager->persist($signalement);
        $this->entityManager->flush();
    }
}
