<?php

namespace App\Messenger\MessageHandler;

use App\Messenger\Message\SignalementUpdateFromAddressMessage;
use App\Repository\SignalementRepository;
use App\Service\Signalement\SignalementAddressUpdater;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class SignalementUpdateFromAddressMessageHandler
{
    public function __construct(
        private readonly SignalementRepository $signalementRepository,
        private readonly SignalementAddressUpdater $signalementAddressUpdater,
        private readonly EntityManagerInterface $entityManager,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function __invoke(SignalementUpdateFromAddressMessage $signalementUpdateFromAddressMessage): void
    {
        try {
            $signalement = $this->signalementRepository->find($signalementUpdateFromAddressMessage->getSignalementId());
            $this->signalementAddressUpdater->updateAddressOccupantFromBanData($signalement);
            $this->entityManager->flush();
        } catch (\Throwable $exception) {
            $this->logger->error(
                sprintf(
                    'The update from address of the signalement (%s) failed for the following reason : %s',
                    $signalementUpdateFromAddressMessage->getSignalementId(),
                    $exception->getMessage()
                )
            );
        }
    }
}
