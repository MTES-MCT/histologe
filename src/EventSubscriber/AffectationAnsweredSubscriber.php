<?php

namespace App\EventSubscriber;

use App\Event\AffectationAnsweredEvent;
use App\Manager\SuiviManager;
use App\Service\Sanitizer;
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

    public function onAffectationAnswered(AffectationAnsweredEvent $event)
    {
        $affectation = $event->getAffectation();
        $answer = $event->getAnswer();
        $signalement = $affectation->getSignalement();
        $suivi = $this->suiviManager->createSuivi($this->buildDescriptionFrom($answer));
        $suivi
            ->setCreatedBy($affectation->getAnsweredBy())
            ->setSignalement($signalement);

        $this->suiviManager->save($suivi);
    }

    private function buildDescriptionFrom(array $answer): string
    {
        $description = '';
        if (isset($answer['accept'])) {
            $description = 'Le signalement a été accepté';
        } else {
            $motifRejected = Sanitizer::sanitize($answer['suivi']);
            $description = 'Le signalement à été refusé avec le motif suivant:<br> '.$motifRejected;
        }

        return $description;
    }
}
