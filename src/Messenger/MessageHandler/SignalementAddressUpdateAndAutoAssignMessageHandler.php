<?php

namespace App\Messenger\MessageHandler;

use App\Messenger\Message\SignalementAddressUpdateAndAutoAssignMessage;
use App\Repository\SignalementRepository;
use App\Service\Signalement\AutoAssigner;
use App\Service\Signalement\SignalementAddressUpdater;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class SignalementAddressUpdateAndAutoAssignMessageHandler
{
    public function __construct(
        private readonly SignalementRepository $signalementRepository,
        private readonly SignalementAddressUpdater $signalementAddressUpdater,
        private readonly AutoAssigner $autoAssigner,
        private readonly EntityManagerInterface $entityManager,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function __invoke(SignalementAddressUpdateAndAutoAssignMessage $signalementAddressUpdateAndAutoAssignMessage): void
    {
        try {
            $signalement = $this->signalementRepository->find($signalementAddressUpdateAndAutoAssignMessage->getSignalementId());
            $this->signalementAddressUpdater->updateAddressOccupantFromBanData($signalement);
            $this->autoAssigner->assign($signalement);
            $this->entityManager->flush();
        } catch (\Throwable $exception) {
            $this->logger->error(
                sprintf(
                    'The update from address of the signalement (%s) failed for the following reason : %s',
                    $signalementAddressUpdateAndAutoAssignMessage->getSignalementId(),
                    $exception->getMessage()
                )
            );
        }
    }
}
