<?php

namespace App\Manager;

use App\Dto\Request\Signalement\SignalementDraftRequest;
use App\Entity\Enum\ProfileDeclarant;
use App\Entity\Enum\SignalementDraftStatus;
use App\Entity\SignalementDraft;
use App\Event\SignalementCreatedEvent;
use App\Event\SignalementDraftCompletedEvent;
use App\Factory\SignalementDraftFactory;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class SignalementDraftManager extends AbstractManager
{
    public const LAST_STEP = 'validation_signalement';

    public function __construct(
        protected SignalementDraftFactory $signalementDraftFactory,
        protected EventDispatcherInterface $eventDispatcher,
        protected ManagerRegistry $managerRegistry,
        protected string $entityName = SignalementDraft::class
    ) {
        parent::__construct($managerRegistry, $entityName);
    }

    public function create(
        SignalementDraftRequest $signalementDraftRequest,
        array $payload
    ): ?string {
        $signalementDraft = $this->signalementDraftFactory->createInstanceFrom($signalementDraftRequest, $payload);
        $this->save($signalementDraft);

        return $signalementDraft->getUuid();
    }

    public function update(
        SignalementDraft $signalementDraft,
        SignalementDraftRequest $signalementDraftRequest,
        array $payload
    ): ?string {
        $signalementDraft
            ->setPayload($payload)
            ->setCurrentStep($signalementDraftRequest->getCurrentStep())
            ->setAddressComplete($signalementDraftRequest->getAdresseLogementAdresse())
            ->setEmailDeclarant($this->signalementDraftFactory->getEmailDeclarent($signalementDraftRequest))
            ->setProfileDeclarant(ProfileDeclarant::from(strtoupper($signalementDraftRequest->getProfil())));

        $this->dispatchSignalementDraftCompleted($signalementDraft, $signalementDraftRequest);
        $this->save($signalementDraft);

        return $signalementDraft->getUuid();
    }

    private function dispatchSignalementDraftCompleted(
        SignalementDraft $signalementDraft,
        SignalementDraftRequest $signalementDraftRequest
    ): void {
        if (self::LAST_STEP === $signalementDraftRequest->getCurrentStep()) {
            $signalementDraft->setStatus(SignalementDraftStatus::EN_SIGNALEMENT);
            $signalementDraftCompletedEvent = $this->eventDispatcher->dispatch(
                new SignalementDraftCompletedEvent($signalementDraft),
                SignalementDraftCompletedEvent::NAME
            );

            $signalement = $signalementDraftCompletedEvent->getSignalementDraft()->getSignalements()->first();
            $this->eventDispatcher->dispatch(new SignalementCreatedEvent($signalement), SignalementCreatedEvent::NAME);
        }
    }
}
