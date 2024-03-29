<?php

namespace App\Manager;

use App\Dto\Request\Signalement\SignalementDraftRequest;
use App\Entity\Enum\ProfileDeclarant;
use App\Entity\Enum\SignalementDraftStatus;
use App\Entity\Signalement;
use App\Entity\SignalementDraft;
use App\Event\SignalementCreatedEvent;
use App\Event\SignalementDraftCompletedEvent;
use App\Factory\SignalementDraftFactory;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class SignalementDraftManager extends AbstractManager
{
    public const LAST_STEP = 'validation_signalement';

    public function __construct(
        protected SignalementDraftFactory $signalementDraftFactory,
        protected EventDispatcherInterface $eventDispatcher,
        protected ManagerRegistry $managerRegistry,
        protected UrlGeneratorInterface $urlGenerator,
        protected string $entityName = SignalementDraft::class,
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
    ): ?array {
        $signalementDraft
            ->setPayload($payload)
            ->setCurrentStep($signalementDraftRequest->getCurrentStep())
            ->setAddressComplete($signalementDraftRequest->getAdresseLogementAdresse())
            ->setEmailDeclarant($this->signalementDraftFactory->getEmailDeclarent($signalementDraftRequest))
            ->setProfileDeclarant(ProfileDeclarant::from(strtoupper($signalementDraftRequest->getProfil())));

        if (self::LAST_STEP === $signalementDraftRequest->getCurrentStep()) {
            $signalement = $this->dispatchSignalementDraftCompleted($signalementDraft);
        }
        $this->save($signalementDraft);

        if (SignalementDraftStatus::EN_SIGNALEMENT === $signalementDraft->getStatus() && isset($signalement)) {
            if (ProfileDeclarant::LOCATAIRE === $signalement->getProfileDeclarant()
                || ProfileDeclarant::BAILLEUR_OCCUPANT === $signalementDraft->getProfileDeclarant()
            ) {
                $toRecipient = $signalement->getMailDeclarant();
            } else {
                $toRecipient = $signalement->getMailOccupant();
            }

            return [
                'uuid' => $signalementDraft->getUuid(),
                'signalementReference' => $signalement->getReference(),
                'lienSuivi' => $this->urlGenerator->generate(
                    'front_suivi_signalement',
                    [
                    'code' => $signalement->getCodeSuivi(),
                    'from' => $toRecipient,
                ],
                    UrlGeneratorInterface::ABSOLUTE_URL
                ),
            ];
        }

        return [
            'uuid' => $signalementDraft->getUuid(),
        ];
    }

    private function dispatchSignalementDraftCompleted(
        SignalementDraft $signalementDraft,
    ): Signalement {
        $signalementDraft->setStatus(SignalementDraftStatus::EN_SIGNALEMENT);
        $signalementDraftCompletedEvent = $this->eventDispatcher->dispatch(
            new SignalementDraftCompletedEvent($signalementDraft),
            SignalementDraftCompletedEvent::NAME
        );

        $signalement = $signalementDraftCompletedEvent->getSignalementDraft()->getSignalements()->first();
        $this->eventDispatcher->dispatch(new SignalementCreatedEvent($signalement), SignalementCreatedEvent::NAME);

        return $signalement;
    }
}
