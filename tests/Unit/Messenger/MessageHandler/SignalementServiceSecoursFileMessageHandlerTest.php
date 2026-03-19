<?php

namespace App\Tests\Unit\Messenger\MessageHandler;

use App\Entity\Signalement;
use App\Messenger\Message\SignalementServiceSecoursFileMessage;
use App\Messenger\MessageHandler\SignalementServiceSecoursFileMessageHandler;
use App\Repository\SignalementRepository;
use App\Service\Files\SignalementFileAttacher;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class SignalementServiceSecoursFileMessageHandlerTest extends TestCase
{
    /**
     * @throws \JsonException
     */
    public function testInvokeAttachesFilesFlushesAndClearsUploadedFiles(): void
    {
        $signalementId = 123;
        $uploadedFiles = [
            [
                'slug' => 'file-1',
                'file' => '/tmp/file-1.pdf',
                'titre' => 'Titre 1',
                'description' => 'Description 1',
            ],
            [
                'slug' => 'file-2',
                'file' => '/tmp/file-2.pdf',
                'titre' => 'Titre 2',
                'description' => 'Description 2',
            ],
        ];

        $jsonContent = [
            'uploadedFiles' => $uploadedFiles,
            'otherKey' => 'otherValue',
        ];

        $message = new SignalementServiceSecoursFileMessage($signalementId);

        $signalement = $this->createMock(Signalement::class);
        $signalement
            ->expects(self::once())
            ->method('getJsonContent')
            ->willReturn($jsonContent);

        $signalementRepository = $this->createMock(SignalementRepository::class);
        $signalementRepository
            ->expects(self::once())
            ->method('find')
            ->with($signalementId)
            ->willReturn($signalement);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager
            ->expects(self::once())
            ->method('flush');

        $logger = $this->createMock(LoggerInterface::class);
        $expectedFiles = $uploadedFiles;
        $callIndex = 0;
        $signalementFileAttacher = $this->createMock(SignalementFileAttacher::class);
        $signalementFileAttacher
            ->expects(self::exactly(2))
            ->method('createAndAttach')
            ->willReturnCallback(function ($signalementArg, $uploadedFileArg) use ($signalement, $expectedFiles, &$callIndex) {
                self::assertSame($signalement, $signalementArg);
                self::assertSame($expectedFiles[$callIndex], $uploadedFileArg);
                ++$callIndex;
            });

        $handler = new SignalementServiceSecoursFileMessageHandler(
            $signalementRepository,
            $entityManager,
            $logger,
            $signalementFileAttacher,
        );

        $handler($message);
    }
}
