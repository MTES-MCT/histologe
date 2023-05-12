<?php

namespace App\Messenger\MessageHandler;

use App\Entity\JobEvent;
use App\Manager\JobEventManager;
use App\Messenger\Message\DossierMessageSCHS;
use App\Repository\PartnerRepository;
use App\Service\Esabora\AbstractEsaboraService;
use App\Service\Esabora\EsaboraSCHSService;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

#[AsMessageHandler]
final class DossierMessageSCHSHandler
{
    public function __construct(
        private readonly EsaboraSCHSService $esaboraService,
        private readonly JobEventManager $jobEventManager,
        private readonly SerializerInterface $serializer,
        private readonly PartnerRepository $partnerRepository,
    ) {
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     */
    public function __invoke(DossierMessageSCHS $schsDossierMessage): void
    {
        $response = $this->esaboraService->pushDossier($schsDossierMessage);
        $partner = $this->partnerRepository->find($partnerId = $schsDossierMessage->getPartnerId());

        $this->jobEventManager->createJobEvent(
            service: AbstractEsaboraService::TYPE_SERVICE,
            action: AbstractEsaboraService::ACTION_PUSH_DOSSIER,
            message: $this->serializer->serialize($schsDossierMessage, 'json'),
            response: $response->getContent(),
            status: 200 === $response->getStatusCode() ? JobEvent::STATUS_SUCCESS : JobEvent::STATUS_FAILED,
            codeStatus: $response->getStatusCode(),
            signalementId: $schsDossierMessage->getSignalementId(),
            partnerId: $partnerId,
            partnerType: $partner?->getType(),
        );
    }
}
