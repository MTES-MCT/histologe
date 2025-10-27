<?php

namespace App\EventSubscriber;

use App\Entity\Enum\InterfacageType;
use App\Entity\JobEvent;
use App\Manager\JobEventManager;
use App\Messenger\Message\DossierMessageInterface;
use App\Repository\PartnerRepository;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\Event\WorkerMessageFailedEvent;
use Symfony\Component\Serializer\SerializerInterface;

readonly class WorkerMessageEventSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private JobEventManager $jobEventManager,
        private SerializerInterface $serializer,
        private PartnerRepository $partnerRepository,
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
                    action: $dossierMessage->getAction(),
                    message: $this->serializer->serialize($dossierMessage, 'json'),
                    response: (string) json_encode($error),
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
