<?php

namespace App\EventSubscriber;

use App\Event\AffectationAnsweredEvent;
use App\Manager\SuiviManager;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class AffectationAnsweredSubscriber implements EventSubscriberInterface
{
    public function __construct(private SuiviManager $suiviManager)
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
        $signalement = $affectation->getSignalement();
        $suivi = $this->suiviManager->createSuivi($params);
        $suivi
            ->setCreatedBy($affectation->getAnsweredBy())
            ->setSignalement($signalement);

        $this->suiviManager->save($suivi);
    }
}
