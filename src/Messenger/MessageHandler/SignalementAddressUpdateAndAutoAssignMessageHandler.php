<?php

namespace App\Messenger\MessageHandler;

use App\Entity\Enum\SignalementStatus;
use App\Messenger\Message\SignalementDraftProcessMessage;
use App\Repository\SignalementRepository;
use App\Service\NotificationAndMailSender;
use App\Service\Signalement\AutoAssigner;
use App\Service\Signalement\SignalementAddressUpdater;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(priority: 0)]
class SignalementAddressUpdateAndAutoAssignMessageHandler
{
    public function __construct(
        private readonly SignalementRepository $signalementRepository,
        private readonly SignalementAddressUpdater $signalementAddressUpdater,
        private readonly AutoAssigner $autoAssigner,
        private readonly EntityManagerInterface $entityManager,
        private readonly NotificationAndMailSender $notificationAndMailSender,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function __invoke(SignalementDraftProcessMessage $signalementDraftProcessMessage): void
    {
        $this->logger->info('Start handling SignalementAddressUpdateAndAutoAssignMessageHandler', [
            'signalementDraftId' => $signalementDraftProcessMessage->getSignalementDraftId(),
            'signalementId' => $signalementDraftProcessMessage->getSignalementId(),
            'step' => 'auto-assign',
        ]);

        try {
            $signalement = $this->signalementRepository->find($signalementDraftProcessMessage->getSignalementId());
            $this->signalementAddressUpdater->updateAddressOccupantFromBanData($signalement);
            $this->entityManager->flush();
            if (SignalementStatus::INJONCTION_BAILLEUR === $signalement->getStatut()) {
                $this->notificationAndMailSender->sendNewSignalementInjonction($signalement);
            } else {
                $this->autoAssigner->assignOrSendNewSignalementNotification($signalement);
            }
        } catch (\Throwable $exception) {
            $this->logger->error(
                sprintf(
                    'The update from address of the signalement (%s) failed for the following reason : %s',
                    $signalementDraftProcessMessage->getSignalementId(),
                    $exception->getMessage()
                )
            );
        }

        $this->logger->info('SignalementAddressUpdateAndAutoAssignMessageHandler handled successfully', [
            'signalementId' => $signalementDraftProcessMessage->getSignalementId(),
            'step' => 'auto-assign',
        ]);
    }
}
