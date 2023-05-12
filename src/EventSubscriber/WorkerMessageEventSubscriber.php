<?php

namespace App\EventSubscriber;

use App\Entity\Enum\InterfacageType;
use App\Entity\JobEvent;
use App\Manager\JobEventManager;
use App\Messenger\Message\DossierMessageInterface;
use App\Repository\PartnerRepository;
use App\Service\Esabora\AbstractEsaboraService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\Event\WorkerMessageFailedEvent;
use Symfony\Component\Serializer\SerializerInterface;

class WorkerMessageEventSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly JobEventManager $jobEventManager,
        private readonly SerializerInterface $serializer,
        private readonly PartnerRepository $partnerRepository,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            WorkerMessageFailedEvent::class => 'createJobEvent',
        ];
    }

    public function createJobEvent(WorkerMessageFailedEvent $event): void
    {
        if (!$event->willRetry()) {
            $dossierMessage = $event->getEnvelope()->getMessage();
            if ($dossierMessage instanceof DossierMessageInterface) {
                $error = [
                  'message' => $event->getThrowable()->getMessage(),
                  'stack_trace' => $event->getThrowable()->getTraceAsString(),
                ];
                $partner = $this->partnerRepository->find($partnerId = $dossierMessage->getPartnerId());
                $this->jobEventManager->createJobEvent(
                    service: InterfacageType::ESABORA->value,
                    action: AbstractEsaboraService::ACTION_PUSH_DOSSIER,
                    message: $this->serializer->serialize($dossierMessage, 'json'),
                    response: json_encode($error),
                    status: JobEvent::STATUS_FAILED,
                    codeStatus: Response::HTTP_SERVICE_UNAVAILABLE,
                    signalementId: $dossierMessage->getSignalementId(),
                    partnerId: $partnerId,
                    partnerType: $partner?->getType()
                );
            }
        }
    }
}
