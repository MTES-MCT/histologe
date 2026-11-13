<?php

namespace App\Tests\Unit\Messenger\MessageHandler;

use App\Entity\File;
use App\Entity\Signalement;
use App\Entity\User;
use App\Manager\FileManager;
use App\Messenger\Message\GenerateFileZipMessage;
use App\Messenger\MessageHandler\GenerateFileZipMessageHandler;
use App\Repository\SignalementRepository;
use App\Repository\UserRepository;
use App\Service\Files\ZipStreamBuilder;
use App\Service\Mailer\NotificationMail;
use App\Service\Mailer\NotificationMailerRegistry;
use App\Service\UploadHandlerService;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class GenerateFileZipMessageHandlerTest extends TestCase
{
    private MockObject&UserRepository $userRepository;
    private MockObject&SignalementRepository $signalementRepository;
    private Stub&ZipStreamBuilder $zipBuilder;
    private MockObject&UploadHandlerService $uploadHandlerService;
    private MockObject&NotificationMailerRegistry $notificationMailerRegistry;
    private MockObject&FileManager $fileManager;
    private MockObject&LoggerInterface $logger;
    private Stub&EntityManagerInterface $entityManager;
    private GenerateFileZipMessageHandler $handler;

    protected function setUp(): void
    {
        $this->userRepository = $this->createMock(UserRepository::class);
        $this->signalementRepository = $this->createMock(SignalementRepository::class);
        $this->zipBuilder = $this->createStub(ZipStreamBuilder::class);
        $this->uploadHandlerService = $this->createMock(UploadHandlerService::class);
        $this->notificationMailerRegistry = $this->createMock(NotificationMailerRegistry::class);
        $this->fileManager = $this->createMock(FileManager::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->entityManager = $this->createStub(EntityManagerInterface::class);

        $this->handler = new GenerateFileZipMessageHandler(
            $this->userRepository,
            $this->signalementRepository,
            $this->zipBuilder,
            $this->uploadHandlerService,
            $this->notificationMailerRegistry,
            $this->fileManager,
            $this->logger,
            $this->entityManager,
        );
    }

    /**
     * @throws \DateInvalidTimeZoneException
     */
    public function testHandleSuccess(): void
    {
        $user = (new User())->setEmail('test@yopmail.com');

        $signalement = $this->createStub(Signalement::class);
        $signalement->method('getUuid')->willReturn('00000000-0000-0000-2025-000000000010');
        $signalement->method('getFiles')->willReturn(new ArrayCollection());
        $file = (new File())->setUuid('file-uuid-123');

        $message = new GenerateFileZipMessage(
            userId: 1,
            signalementId: 1,
            fileIds: [10, 20]
        );

        $this->userRepository
            ->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn($user);

        $this->signalementRepository
            ->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn($signalement);

        $this->zipBuilder->method('create')->willReturnSelf();
        $this->zipBuilder->method('addMany')->willReturnSelf();
        $this->zipBuilder->method('close')->willReturn('/tmp/test.zip');

        $this->uploadHandlerService->expects($this->once())
            ->method('uploadFromFilename')
            ->willReturn('final-filename.zip');

        $this->fileManager->expects($this->once())
            ->method('createOrUpdate')
            ->willReturn($file);

        $this->notificationMailerRegistry->expects($this->once())
            ->method('send')
            ->with($this->isInstanceOf(NotificationMail::class));

        $this->logger->expects($this->never())->method('error');

        ($this->handler)($message);
    }

    /**
     * @throws \DateInvalidTimeZoneException
     */
    public function testHandleFailure(): void
    {
        $user = new User();
        $signalement = new Signalement();
        $message = new GenerateFileZipMessage(1, 1, []);

        $this->userRepository->expects($this->once())->method('find')->willReturn($user);
        $this->signalementRepository->expects($this->once())->method('find')->willReturn($signalement);

        $this->zipBuilder->method('create')->willThrowException(new \Exception('Zip Error'));

        $this->uploadHandlerService->expects($this->never())->method('uploadFromFilename');
        $this->fileManager->expects($this->never())->method('createOrUpdate');
        $this->notificationMailerRegistry->expects($this->never())->method('send');

        $this->logger->expects($this->once())
            ->method('error')
            ->with('Zip Error');

        ($this->handler)($message);
    }
}
