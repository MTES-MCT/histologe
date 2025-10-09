<?php

namespace App\Tests\Unit\Command\Cron;

use App\Command\Cron\MonitorMessengerQueuesCommand;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
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

    protected function setUp(): void
    {
        parent::setUp();

        $this->connection = $this->createMock(Connection::class);
        $this->messengerSerializer = $this->createMock(MessengerSerializerInterface::class);
        $this->serializer = $this->createMock(SerializerInterface::class);
    }

    public function testDisplaysOkMessageWhenNoOldMessagesFound(): void
    {
        $threshold = '6 HOUR';

        $this->connection
            ->method('fetchAllAssociative')
            ->willReturn([]);

        $command = new MonitorMessengerQueuesCommand(
            $this->connection,
            $this->messengerSerializer,
            $this->serializer,
            $threshold
        );

        $tester = new CommandTester($command);
        $exitCode = $tester->execute([]);
        $display = $tester->getDisplay();

        self::assertSame(0, $exitCode, 'La commande doit retourner un code de succès.');
        self::assertStringContainsString(
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
            $threshold
        );

        $tester = new CommandTester($command);

        $exitCode = $tester->execute([]);
        $display = $tester->getDisplay();

        self::assertSame(0, $exitCode);
        self::assertStringContainsString('Messenger queue "default" stalled', $display);
        self::assertStringContainsString('anonymous', $display);
    }
}
