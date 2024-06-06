<?php

namespace App\Messenger\MessageHandler\Idoss;

use App\Entity\JobEvent;
use App\Manager\JobEventManager;
use App\Messenger\Message\Idoss\DossierMessage;
use App\Service\Idoss\IdossService;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Serializer\SerializerInterface;

#[AsMessageHandler]
class DossierMessageHandler
{
    public function __construct(
        private readonly IdossService $idossService,
        private readonly JobEventManager $jobEventManager,
        private readonly SerializerInterface $serializer,
    ) {
    }

    public function __invoke(DossierMessage $dossierMessage): void
    {
        try {
            $response = $this->idossService->pushDossier($dossierMessage);
            $statusCode = $response->getStatusCode();
            $status = 200 === $statusCode ? JobEvent::STATUS_SUCCESS : JobEvent::STATUS_FAILED;
            $responseContent = $response->getContent(throw: false);
        } catch (\Exception $e) {
            $responseContent = $e->getMessage();
            $status = JobEvent::STATUS_FAILED;
            $statusCode = 9999;
        }

        $this->jobEventManager->createJobEvent(
            service: IdossService::TYPE_SERVICE,
            action: IdossService::ACTION_PUSH_DOSSIER,
            message: $this->serializer->serialize($dossierMessage, 'json'),
            response: $responseContent,
            status: $status,
            codeStatus: $statusCode,
            signalementId: $dossierMessage->getSignalementId(),
            partnerId: $dossierMessage->getPartnerId(),
            partnerType: $dossierMessage->getPartnerType(),
        );
    }
}
