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
use App\Repository\SignalementDraftRepository;
use App\Serializer\SignalementDraftRequestSerializer;
use App\Service\Signalement\SignalementDraftHelper;
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
        protected SignalementDraftRequestSerializer $signalementDraftRequestSerializer,
        protected SignalementDraftRepository $signalementDraftRepository,
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
            ->setEmailDeclarant(SignalementDraftHelper::getEmailDeclarant($signalementDraftRequest))
            ->setProfileDeclarant(ProfileDeclarant::from(strtoupper($signalementDraftRequest->getProfil())));

        if (self::LAST_STEP === $signalementDraftRequest->getCurrentStep()) {
            if (SignalementDraftStatus::EN_SIGNALEMENT === $signalementDraft->getStatus()) {
                $signalement = $signalementDraft->getSignalements()->first();
            } else {
                $signalement = $this->dispatchSignalementDraftCompleted($signalementDraft);
            }
        }
        $this->save($signalementDraft);

        if (SignalementDraftStatus::EN_SIGNALEMENT === $signalementDraft->getStatus() && isset($signalement)) {
            return [
                'uuid' => $signalementDraft->getUuid(),
                'signalementReference' => $signalement->getReference(),
                'lienSuivi' => $this->urlGenerator->generate(
                    'front_suivi_signalement',
                    [
                    'code' => $signalement->getCodeSuivi(),
                    'from' => $signalement->getMailDeclarant(),
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
    ): ?Signalement {
        $signalementDraftCompletedEvent = $this->eventDispatcher->dispatch(
            new SignalementDraftCompletedEvent($signalementDraft),
            SignalementDraftCompletedEvent::NAME
        );

        $signalement = $signalementDraftCompletedEvent->getSignalementDraft()->getSignalements()->first();
        if ($signalement) {
            $signalementDraft->setStatus(SignalementDraftStatus::EN_SIGNALEMENT);
            $this->eventDispatcher->dispatch(new SignalementCreatedEvent($signalement), SignalementCreatedEvent::NAME);

            return $signalement;
        }

        return null;
    }

    public function findSignalementDraftByAddressAndMail(
        SignalementDraftRequest $signalementDraftRequest,
    ): ?SignalementDraft {
        $dataToHash = SignalementDraftHelper::getEmailDeclarant($signalementDraftRequest);
        $dataToHash .= $signalementDraftRequest->getAdresseLogementAdresse();
        $hash = hash('sha256', $dataToHash);

        return $this->signalementDraftRepository->findOneBy(
            [
                'checksum' => $hash,
                'status' => SignalementDraftStatus::EN_COURS,
            ],
            [
                'id' => 'DESC',
            ]
        );
    }
}
