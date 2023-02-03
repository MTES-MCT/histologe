<?php

namespace App\EventSubscriber;

use App\Entity\JobEvent;
use App\Manager\JobEventManager;
use App\Messenger\Message\DossierMessage;
use App\Service\Esabora\EsaboraService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Messenger\Event\WorkerMessageFailedEvent;
use Symfony\Component\Serializer\SerializerInterface;

class WorkerMessageEventSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private JobEventManager $jobEventManager,
        private SerializerInterface $serializer,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            WorkerMessageFailedEvent::class => 'createJobEvent',
        ];
    }

    public function createJobEvent(WorkerMessageFailedEvent $event)
    {
        if (!$event->willRetry()) {
            $dossierMessage = $event->getEnvelope()->getMessage();
            if ($dossierMessage instanceof DossierMessage) {
                $error = [
                  'message' => $event->getThrowable()->getMessage(),
                  'stack_trace' => $event->getThrowable()->getTraceAsString(),
                ];
                $this->jobEventManager->createJobEvent(
                    type: EsaboraService::TYPE_SERVICE,
                    title: EsaboraService::ACTION_PUSH_DOSSIER,
                    message: $this->serializer->serialize($dossierMessage, 'json'),
                    response: json_encode($error),
                    status: JobEvent::STATUS_FAILED,
                    signalementId: $dossierMessage->getSignalementId(),
                    partnerId: $dossierMessage->getPartnerId()
                );
            }
        }
    }
}
