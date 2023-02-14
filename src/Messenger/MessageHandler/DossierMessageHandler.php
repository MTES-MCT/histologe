<?php

namespace App\Messenger\MessageHandler;

use App\Entity\JobEvent;
use App\Manager\JobEventManager;
use App\Messenger\Message\DossierMessage;
use App\Service\Esabora\EsaboraService;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

#[AsMessageHandler]
final class DossierMessageHandler
{
    public function __construct(
        private EsaboraService $esaboraService,
        private JobEventManager $jobEventManager,
        private SerializerInterface $serializer,
    ) {
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     */
    public function __invoke(DossierMessage $dossierMessage): void
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
