<?php

namespace App\EventSubscriber;

use App\Entity\Suivi;
use App\Event\AffectationAnsweredEvent;
use App\Manager\SuiviManager;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class AffectationAnsweredSubscriber implements EventSubscriberInterface
{
    public function __construct(private readonly SuiviManager $suiviManager)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            AffectationAnsweredEvent::NAME => 'onAffectationAnswered',
        ];
    }

    public function onAffectationAnswered(AffectationAnsweredEvent $event): void
    {
        $affectation = $event->getAffectation();
        $params = $event->getParams();
        $user = $event->getUser();
        $signalement = $affectation->getSignalement();
        $this->suiviManager->createSuivi(
            user: $user,
            signalement: $signalement,
            description: SuiviManager::buildDescriptionAnswerAffectation($params),
            type: Suivi::TYPE_AUTO,
        );
    }
}
