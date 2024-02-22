<?php

namespace App\Tests\Functional\Messenger\MessageHandler;

use App\Messenger\Message\SignalementDraftFileMessage;
use App\Messenger\MessageHandler\SignalementDraftFileMessageHandler;
use App\Repository\SignalementDraftRepository;
use App\Repository\SignalementRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Messenger\MessageBusInterface;

class SignalementDraftFileMessageHandlerTest extends KernelTestCase
{
    private MessageBusInterface $messageBus;
    private SignalementDraftRepository $signalementDraftRepository;
    private SignalementRepository $signalementRepository;
    private SignalementDraftFileMessageHandler $signalementDraftFileMessageHandler;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->messageBus = static::getContainer()->get(MessageBusInterface::class);
        $this->signalementDraftRepository = static::getContainer()->get(SignalementDraftRepository::class);
        $this->signalementRepository = static::getContainer()->get(SignalementRepository::class);
        $this->signalementDraftFileMessageHandler =
            static::getContainer()->get(SignalementDraftFileMessageHandler::class);
    }

    public function testHandleMessageWithSuccess(): void
    {
        $signalementDraft = $this->signalementDraftRepository->findOneBy(
            ['uuid' => '00000000-0000-0000-2024-locataire002']
        );
        $signalement = $this->signalementRepository->findOneBy(['uuid' => '00000000-0000-0000-2024-000000000003']);
        $this->assertCount(0, $signalement->getFiles());
        $message = new SignalementDraftFileMessage($signalementDraft->getId(), $signalement->getId());

        $this->messageBus->dispatch($message);
        $transport = static::getContainer()->get('messenger.transport.async_priority_high');
        $envelopes = $transport->get();
        $this->assertCount(1, $envelopes);

        $handler = $this->signalementDraftFileMessageHandler;
        $handler($message);
        $this->assertCount(7, $signalement->getFiles());
    }

    public function testHandleMessageWithFailure(): void
    {
        $signalementDraft = $this->signalementDraftRepository->findOneBy([
            'uuid' => '00000000-0000-0000-2024-locataire002',
        ]);
        $signalement = $this->signalementRepository->findOneBy(['uuid' => '00000000-0000-0000-2024-000000000003']);
        $message = new SignalementDraftFileMessage($signalementDraft->getId(), $signalement->getId());

        $this->expectException(\Throwable::class);

        $this->messageBus->dispatch($message, ['simulate_exception' => true]);
        $transport = static::getContainer()->get('messenger.transport.failed_high_priority');
        $envelopes = $transport->get();
        $this->assertCount(1, $envelopes);

        $handler = $this->signalementDraftFileMessageHandler;
        $handler($message);
        $this->assertCount(0, $signalement->getFiles());
    }
}
