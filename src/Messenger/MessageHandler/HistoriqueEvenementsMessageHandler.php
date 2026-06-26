<?php

namespace App\Messenger\MessageHandler;

use App\Messenger\Message\SignalementDraftProcessMessage;
use App\Repository\SignalementRepository;
use App\Service\Signalement\Suivi\HistoriqueEvenementsGenerator;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(priority: -10)]
class HistoriqueEvenementsMessageHandler
{
    public function __construct(
        private readonly SignalementRepository $signalementRepository,
        private readonly HistoriqueEvenementsGenerator $historiqueEvenementsGenerator,
        private readonly LoggerInterface $logger,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function __invoke(SignalementDraftProcessMessage $signalementDraftProcessMessage): void
    {
        $this->logger->info('Start handling HistoriqueEvenementsMessageHandler', [
            'signalementDraftId' => $signalementDraftProcessMessage->getSignalementDraftId(),
            'signalementId' => $signalementDraftProcessMessage->getSignalementId(),
            'step' => 'historique-evenements',
        ]);

        $signalement = $this->signalementRepository->find($signalementDraftProcessMessage->getSignalementId());
        if (null !== $signalement) {
            $this->historiqueEvenementsGenerator->generate($signalement);
            $this->entityManager->flush();
            $this->logger->info('HistoriqueEvenementsMessageHandler handled successfully', [
                'signalementId' => $signalementDraftProcessMessage->getSignalementId(),
                'step' => 'historique-evenements',
            ]);
        } else {
            $this->logger->error('HistoriqueEvenementsMessageHandler failed', [
                'signalementDraftId' => $signalementDraftProcessMessage->getSignalementDraftId(),
                'signalementId' => $signalementDraftProcessMessage->getSignalementId(),
                'step' => 'historique-evenements',
            ]);
        }
    }
}
