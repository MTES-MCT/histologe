<?php

namespace App\Messenger\MessageHandler;

use App\Entity\JobEvent;
use App\Manager\JobEventManager;
use App\Messenger\Message\DossierMessage;
use App\Repository\PartnerRepository;
use App\Service\Esabora\EsaboraSCHSService;
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
    public function __invoke(DossierMessage $dossierMessage): void
    {
        $response = $this->esaboraService->pushDossier($dossierMessage);
        $partner = $this->partnerRepository->find($partnerId = $dossierMessage->getPartnerId());

        $this->jobEventManager->createJobEvent(
            service: EsaboraSCHSService::TYPE_SERVICE,
            action: EsaboraSCHSService::ACTION_PUSH_DOSSIER,
            message: $this->serializer->serialize($dossierMessage, 'json'),
            response: $response->getContent(),
            status: 200 === $response->getStatusCode() ? JobEvent::STATUS_SUCCESS : JobEvent::STATUS_FAILED,
            codeStatus: $response->getStatusCode(),
            signalementId: $dossierMessage->getSignalementId(),
            partnerId: $partnerId,
            partnerType: $partner?->getType(),
        );
    }
}
