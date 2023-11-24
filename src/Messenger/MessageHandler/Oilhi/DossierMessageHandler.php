<?php

namespace App\Messenger\MessageHandler\Oilhi;

use App\Entity\JobEvent;
use App\Manager\JobEventManager;
use App\Messenger\Message\Oilhi\DossierMessage;
use App\Repository\PartnerRepository;
use App\Service\Oilhi\HookZapierService;
use Symfony\Component\Serializer\SerializerInterface;

class DossierMessageHandler
{
    public function __construct(
        private SerializerInterface $serializer,
        private JobEventManager $jobEventManager,
        private HookZapierService $hookZapierService,
        private PartnerRepository $partnerRepository,
    ) {
    }

    public function __invoke(DossierMessage $dossierMessage): void
    {
        $response = $this->hookZapierService->pushDossier($dossierMessage);
        $partner = $this->partnerRepository->find($partnerId = $dossierMessage->getPartnerId());

        $this->jobEventManager->createJobEvent(
            service: HookZapierService::TYPE_SERVICE,
            action: HookZapierService::ACTION_PUSH_DOSSIER,
            message: $this->serializer->serialize($dossierMessage, 'json'),
            response: $response->getContent(throw: false),
            status: 200 === $response->getStatusCode() ? JobEvent::STATUS_SUCCESS : JobEvent::STATUS_FAILED,
            codeStatus: $response->getStatusCode(),
            signalementId: $dossierMessage->getSignalementId(),
            partnerId: $dossierMessage->getPartnerId(),
            partnerType: $partner?->getType(),
        );
    }
}
