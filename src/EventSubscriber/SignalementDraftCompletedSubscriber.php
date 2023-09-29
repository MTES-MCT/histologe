<?php

namespace App\EventSubscriber;

use App\Event\SignalementDraftCompletedEvent;
use App\Manager\SignalementManager;
use App\Service\Signalement\SignalementBuilder;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class SignalementDraftCompletedSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private SignalementBuilder $signalementBuilder,
        private SignalementManager $signalementManager
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            SignalementDraftCompletedEvent::NAME => 'onSignalementDraftCompleted',
        ];
    }

    public function onSignalementDraftCompleted(SignalementDraftCompletedEvent $event): void
    {
        $signalementDraft = $event->getSignalementDraft();

        $signalement = $this->signalementBuilder
            ->createSignalementBuilderFrom($signalementDraft)
            ->withAdressesCoordonnees()
            ->withTypeCompositionLogement()
            ->withSituationFoyer()
            ->withProcedure()
            ->withInformationComplementaire()
            ->build();

        $this->signalementManager->save($signalement);
    }
}
