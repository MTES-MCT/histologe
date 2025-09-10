<?php

namespace App\EventSubscriber;

use App\Entity\Enum\SuiviCategory;
use App\Entity\Suivi;
use App\Entity\User;
use App\Event\SignalementClosedEvent;
use App\Manager\SuiviManager;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

readonly class SignalementClosedSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private SuiviManager $suiviManager,
        private Security $security,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            SignalementClosedEvent::NAME => 'onSignalementClosed',
        ];
    }

    public function onSignalementClosed(SignalementClosedEvent $event): void
    {
        $signalementAffectationClose = $event->getSignalementAffectationClose();
        $signalement = $signalementAffectationClose->getSignalement();
        /** @var User $user */
        $user = $this->security->getUser();
        $signalement->setClosedBy($user);

        $suivi = $this->suiviManager->createSuivi(
            signalement: $signalement,
            description: SuiviManager::buildDescriptionClotureSignalement(
                [
                    'subject' => $signalementAffectationClose->getSubject(),
                    'motif_cloture' => $signalementAffectationClose->getMotifCloture(),
                    'motif_suivi' => $signalementAffectationClose->getDescription(),
                ]
            ),
            type: Suivi::TYPE_PARTNER,
            category: SuiviCategory::SIGNALEMENT_IS_CLOSED,
            partner: $event->getPartner(),
            user: $user,
            isPublic: $signalementAffectationClose->isPublic(),
            context: Suivi::CONTEXT_SIGNALEMENT_CLOSED,
            files: $signalementAffectationClose->getFiles(),
        );
        $signalement->addSuivi($suivi);
    }
}
