<?php

namespace App\Tests\Unit\Command\Cron;

use App\Command\Cron\MonitorMessengerQueuesCommand;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Transport\Serialization\SerializerInterface as MessengerSerializerInterface;
use Symfony\Component\Serializer\SerializerInterface;

final class MonitorMessengerQueuesCommandTest extends TestCase
{
    /** @var Connection&MockObject */
    private Connection $connection;

    /** @var MessengerSerializerInterface&MockObject */
    private MessengerSerializerInterface $messengerSerializer;

    /** @var SerializerInterface&MockObject */
    private SerializerInterface $serializer;

    /* @var LoggerInterface&MockObject */
    private LoggerInterface $logger;

    protected function setUp(): void
    {
        parent::setUp();

        $this->connection = $this->createMock(Connection::class);
        $this->messengerSerializer = $this->createMock(MessengerSerializerInterface::class);
        $this->serializer = $this->createMock(SerializerInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
    }

    public function testDisplaysOkMessageWhenNoOldMessagesFound(): void
    {
        $threshold = '1 HOUR';

        $this->connection
            ->method('fetchAllAssociative')
            ->willReturn([]);

        $command = new MonitorMessengerQueuesCommand(
            $this->connection,
            $this->messengerSerializer,
            $this->serializer,
            $this->logger,
            $threshold
        );

        $tester = new CommandTester($command);
        $exitCode = $tester->execute([]);
        $display = $tester->getDisplay();

        $this->assertSame(0, $exitCode, 'La commande doit retourner un code de succès.');
        $this->assertStringContainsString(
            'OK, no old messages found.',
            $display,
            'Le message de sortie attendu doit être affiché.'
        );
    }

    public function testDisplaysMessageWhenOldMessagesFound(): void
    {
        // Arrange
        $threshold = '6 HOUR';

        // Simule un message en base
        $row = [
            'id' => 1,
            'queue_name' => 'default',
            'body' => '{}',
            'created_at' => '2025-10-01 10:00:00',
        ];

        $this->connection
            ->method('fetchAllAssociative')
            ->willReturn([$row]);

        $dummyMessage = new class {
            public string $name = 'test-message';
        };

        $this->messengerSerializer
            ->method('decode')
            ->willReturn(new Envelope($dummyMessage));

        $this->serializer
            ->method('serialize')
            ->willReturn('{"name":"test-message"}');

        $command = new MonitorMessengerQueuesCommand(
            $this->connection,
            $this->messengerSerializer,
            $this->serializer,
            $this->logger,
            $threshold
        );

        $tester = new CommandTester($command);

        $exitCode = $tester->execute([]);
        $display = $tester->getDisplay();

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('Messenger queue "default" stalled', $display);
        $this->assertStringContainsString('anonymous', $display);
    }

    public function testContinuesWhenOneMessageCannotBeSerialized(): void
    {
        $threshold = '6 HOUR';

        $malformedRow = [
            'id' => 1,
            'queue_name' => 'default',
            'body' => '{}',
            'created_at' => '2025-10-01 10:00:00',
        ];

        $validRow = [
            'id' => 2,
            'queue_name' => 'default',
            'body' => '{}',
            'created_at' => '2025-10-01 10:01:00',
        ];

        $this->connection
            ->method('fetchAllAssociative')
            ->willReturn([$malformedRow, $validRow]);

        $malformedMessage = new class {
            public string $name = 'malformed-message';
        };

        $validMessage = new class {
            public string $name = 'valid-message';
        };

        $this->messengerSerializer
            ->method('decode')
            ->willReturnOnConsecutiveCalls(
                new Envelope($malformedMessage),
                new Envelope($validMessage),
            );

        $this->serializer
            ->method('serialize')
            ->willReturnOnConsecutiveCalls(
                $this->throwException(new \InvalidArgumentException('Malformed UTF-8 characters, possibly incorrectly encoded')),
                '{"name":"valid-message"}',
            );

        $this->logger
            ->expects($this->once())
            ->method('error')
            ->with(
                $this->stringContains('Unable to process stalled messenger message "1" from queue "default"'),
                $this->arrayHasKey('exception'),
            );

        $command = new MonitorMessengerQueuesCommand(
            $this->connection,
            $this->messengerSerializer,
            $this->serializer,
            $this->logger,
            $threshold
        );

        $tester = new CommandTester($command);

        $exitCode = $tester->execute([]);
        $display = $tester->getDisplay();

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('Unable to process stalled messenger message "1"', $display);
        $this->assertStringContainsString('Messenger queue "default" stalled', $display);
    }
}
