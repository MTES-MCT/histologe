<?php

namespace App\Tests\Unit\EventSubscriber;

use App\Entity\Enum\InterfacageType;
use App\Entity\JobEvent;
use App\EventSubscriber\WorkerMessageEventSubscriber;
use App\Manager\JobEventManager;
use App\Messenger\Message\Esabora\DossierMessageSCHS;
use App\Repository\PartnerRepository;
use App\Service\Interconnection\Esabora\AbstractEsaboraService;
use App\Tests\FixturesHelper;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Event\WorkerMessageFailedEvent;
use Symfony\Component\Messenger\Stamp\DelayStamp;
use Symfony\Component\Messenger\Stamp\ReceivedStamp;
use Symfony\Component\Serializer\SerializerInterface;

class WorkerMessageEventSubscriberTest extends TestCase
{
    use FixturesHelper;

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
        $serializerMock = $this->createMock(SerializerInterface::class);
        $partnerRepositoryMock = $this->createMock(PartnerRepository::class);
        $subscriber = new WorkerMessageEventSubscriber($jobEventManagerMock, $serializerMock, $partnerRepositoryMock);

        $dossierMessage = new DossierMessageSCHS();
        $dossierMessage->setAction('push_dossier');
        $envelope = new Envelope($dossierMessage, [
            new DelayStamp(0),
            new ReceivedStamp('async'),
        ]);
        $event = new WorkerMessageFailedEvent($envelope, 'async', new \Exception('custom error'));

        /** @var DossierMessageSCHS $dossierMessageFromEnvelope */
        $dossierMessageFromEnvelope = $event->getEnvelope()->getMessage();

        $jobEventManagerMock
            ->expects($this->atLeast(1))
            ->method('createJobEvent')
            ->with(
                InterfacageType::ESABORA->value,
                AbstractEsaboraService::ACTION_PUSH_DOSSIER,
                '',
                json_encode(['message' => 'custom error', 'stack_trace' => $event->getThrowable()->getTraceAsString()]),
                JobEvent::STATUS_FAILED,
                Response::HTTP_SERVICE_UNAVAILABLE,
                null,
                null,
                null
            );

        $dispatcher = new EventDispatcher();
        $dispatcher->addSubscriber($subscriber);
        $dispatcher->dispatch($event, WorkerMessageFailedEvent::class);
    }
}
