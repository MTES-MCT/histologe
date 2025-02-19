<?php

namespace App\EventSubscriber;

use App\Entity\Suivi;
use App\Entity\User;
use App\Event\SignalementClosedEvent;
use App\Manager\SignalementManager;
use App\Manager\SuiviManager;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

readonly class SignalementClosedSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private SignalementManager $signalementManager,
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
        $signalement = $event->getSignalement();
        $params = $event->getParams();
        /** @var User $user */
        $user = $this->security->getUser();
        $signalement->setClosedBy($user);

        $suivi = $this->suiviManager->createSuivi(
            signalement: $signalement,
            description: SuiviManager::buildDescriptionClotureSignalement($params),
            type: Suivi::TYPE_PARTNER,
            isPublic: '1' == $params['suivi_public'],
            user: $user,
            context: Suivi::CONTEXT_SIGNALEMENT_CLOSED,
        );

        $signalement->addSuivi($suivi);

        $this->signalementManager->save($signalement);
    }
}
