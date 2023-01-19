<?php

namespace App\Messenger\MessageHandler;

use App\Entity\JobEvent;
use App\Manager\JobEventManager;
use App\Messenger\Message\DossierMessage;
use App\Service\Esabora\EsaboraService;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use Symfony\Component\Serializer\SerializerInterface;

final class DossierMessageHandler implements MessageHandlerInterface
{
    public function __construct(
        private EsaboraService $esaboraService,
        private JobEventManager $jobEventManager,
        private SerializerInterface $serializer,
    ) {
    }

    public function __invoke(DossierMessage $dossierMessage)
    {
        $response = $this->esaboraService->pushDossier($dossierMessage);
        $this->jobEventManager->createJobEvent(
            type: EsaboraService::TYPE_SERVICE,
            title: EsaboraService::ACTION_PUSH_DOSSIER,
            message: $this->serializer->serialize($dossierMessage, 'json'),
            response: $response->getContent(),
            status: 200 === $response->getStatusCode() ? JobEvent::STATUS_SUCCESS : JobEvent::STATUS_FAILED,
            signalementId: $dossierMessage->getSignalementId(),
            partnerId: $dossierMessage->getPartnerId()
        );
    }
}
