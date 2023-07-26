<?php

namespace App\Manager;

use App\Dto\Request\Signalement\SignalementDraftRequest;
use App\Entity\SignalementDraft;
use App\Factory\SignalementDraftFactory;
use Doctrine\Persistence\ManagerRegistry;

class SignalementDraftManager extends AbstractManager
{
    public function __construct(
        protected SignalementDraftFactory $signalementDraftFactory,
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

        return $signalementDraft->getUuid()->jsonSerialize();
    }

    public function update(
        SignalementDraft $signalementDraft,
        array $payload
    ): ?string {
        $signalementDraft
            ->setPayload($payload)
            ->setCurrentStep('4:type_logement_commodites');

        $this->save($signalementDraft);

        return $signalementDraft->getUuid()->jsonSerialize();
    }
}
