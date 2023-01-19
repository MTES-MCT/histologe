<?php

namespace App\Tests\Unit\EventSubscriber;

use App\Entity\JobEvent;
use App\EventSubscriber\WorkerMessageEventSubscriber;
use App\Manager\JobEventManager;
use App\Messenger\Message\DossierMessage;
use App\Service\Esabora\EsaboraService;
use App\Tests\Unit\Messenger\DossierMessageTrait;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Event\WorkerMessageFailedEvent;
use Symfony\Component\Messenger\Stamp\DelayStamp;
use Symfony\Component\Messenger\Stamp\ReceivedStamp;

class WorkerMessageEventSubscriberTest extends TestCase
{
    use DossierMessageTrait;

    public function testEventSubscription(): void
    {
        $this->assertArrayHasKey(
            WorkerMessageFailedEvent::class,
            WorkerMessageEventSubscriber::getSubscribedEvents()
        );
    }

    public function testOnWorkerMessageFailedEvent(): void
    {
        $jobEventManagerMock = $this->createMock(JobEventManager::class);
        $subscriber = new WorkerMessageEventSubscriber($jobEventManagerMock);

        $dossierMessage = new DossierMessage();
        $envelope = new Envelope($dossierMessage, [
            new DelayStamp(0),
            new ReceivedStamp('async'),
        ]);
        $event = new WorkerMessageFailedEvent($envelope, 'async', new \Exception('custom error'));

        /** @var DossierMessage $dossierMessageFromEnvelope */
        $dossierMessageFromEnvelope = $event->getEnvelope()->getMessage();

        $jobEventManagerMock
            ->expects($this->atLeast(1))
            ->method('createJobEvent')
            ->with(
                EsaboraService::TYPE_SERVICE,
                EsaboraService::ACTION_PUSH_DOSSIER,
                json_encode($dossierMessageFromEnvelope->preparePayload()),
                json_encode(['message' => 'custom error', 'stack_trace' => $event->getThrowable()->getTraceAsString()]),
                JobEvent::STATUS_FAILED,
                null,
                null
            );

        $dispatcher = new EventDispatcher();
        $dispatcher->addSubscriber($subscriber);
        $dispatcher->dispatch($event, WorkerMessageFailedEvent::class);
    }
}
