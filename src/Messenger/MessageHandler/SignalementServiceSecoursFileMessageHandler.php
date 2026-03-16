<?php

namespace App\Messenger\MessageHandler;

use App\Messenger\Message\SignalementServiceSecoursFileMessage;
use App\Repository\SignalementRepository;
use App\Service\Files\SignalementFileAttacher;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class SignalementServiceSecoursFileMessageHandler
{
    public function __construct(
        private readonly SignalementRepository $signalementRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly LoggerInterface $logger,
        private readonly SignalementFileAttacher $signalementFileAttacher,
    ) {
    }

    public function __invoke(SignalementServiceSecoursFileMessage $message): void
    {
        $this->logger->info('Start handling SignalementServiceSecoursFileMessageHandler', [
            'signalementId' => $signalementId = $message->getSignalementId(),
            'step' => 'send-files',
        ]);

        $signalement = $this->signalementRepository->find($signalementId);
        $jsonContent = $signalement->getJsonContent();
        $uploadedFiles = json_decode($jsonContent['uploadedFiles'], true);

        foreach ($uploadedFiles as $uploadedFile) {
            $this->signalementFileAttacher->createAndAttach($signalement, $uploadedFile);
        }

        $signalement->setJsonContent($jsonContent);
        $this->entityManager->flush();

        $this->logger->info('SignalementServiceSecoursFileMessageHandler handled successfully', [
            'signalementId' => $signalementId,
            'nbFiles' => \count($uploadedFiles),
            'step' => 'send-files',
        ]);
    }
}
