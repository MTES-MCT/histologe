<?php

namespace App\Tests\Unit\Service\Signalement;

use App\Factory\FileFactory;
use App\Service\Files\FilenameGenerator;
use App\Service\Files\ImageManipulationHandler;
use App\Service\Security\FileScanner;
use App\Service\Signalement\SignalementFileProcessor;
use App\Service\UploadHandlerService;
use App\Tests\FixturesHelper;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class SignalementFileProcessorTest extends TestCase
{
    use FixturesHelper;

    public const FILE_LIST = [
        'documents' => [
            'sample.pdf' => 'sample.pdf',
            'ramdom.pdf' => 'ramdom.pdf',
        ],
        'photos' => [
            'sample.png' => 'sample.png',
            'ramdom.jpg' => 'ramdom.jpg',
        ],
    ];

    private MockObject&UploadHandlerService $uploadHandlerService;
    private Stub&LoggerInterface $logger;
    private Stub&FilenameGenerator $filenameGenerator;
    private Stub&FileFactory $fileFactory;
    private Stub&ImageManipulationHandler $imageManipulationHandler;
    private Stub&FileScanner $fileScanner;

    protected function setUp(): void
    {
        $this->uploadHandlerService = $this->createMock(UploadHandlerService::class);
        $this->logger = $this->createStub(LoggerInterface::class);
        $this->filenameGenerator = $this->createStub(FilenameGenerator::class);
        $this->fileFactory = $this->createStub(FileFactory::class);
        $this->imageManipulationHandler = $this->createStub(ImageManipulationHandler::class);
        $this->fileScanner = $this->createStub(FileScanner::class);
    }

    public function testProcessUsagerDocument(): void
    {
        $this->uploadHandlerService
            ->expects($this->atLeast(1))
            ->method('moveFromBucketTempFolder')
            ->willReturn('sample-'.uniqid().'.pdf');

        $signalementFileProcessor = new SignalementFileProcessor(
            $this->uploadHandlerService,
            $this->logger,
            $this->filenameGenerator,
            $this->fileFactory,
            $this->imageManipulationHandler,
            $this->fileScanner,
            false
        );

        $fileList = $signalementFileProcessor->process(self::FILE_LIST['documents']);
        $this->assertTrue($signalementFileProcessor->isValid());
        $this->assertEmpty($signalementFileProcessor->getErrorMessages());
        $this->assertCount(2, $fileList);

        foreach ($fileList as $fileItem) {
            $this->assertArrayHasKey('file', $fileItem);
            $this->assertArrayHasKey('title', $fileItem);
            $this->assertArrayHasKey('date', $fileItem);
            $this->assertArrayHasKey('documentType', $fileItem);
            $this->assertArrayHasKey('isSuspicious', $fileItem);
        }
    }

    public function testAddFilesToSignalement(): void
    {
        $this->uploadHandlerService
            ->expects($this->atLeast(1))
            ->method('moveFromBucketTempFolder')
            ->willReturn('sample-'.uniqid().'.pdf');

        $fileFactory = $this->createMock(FileFactory::class);
        $this->fileFactory = $fileFactory;
        $fileFactory
            ->expects($this->atLeast(1))
            ->method('createInstanceFrom')
            ->willReturn($this->getPhotoFile());

        $signalementFileProcessor = new SignalementFileProcessor(
            $this->uploadHandlerService,
            $this->logger,
            $this->filenameGenerator,
            $this->fileFactory,
            $this->imageManipulationHandler,
            $this->fileScanner,
            false
        );
        $signalement = $this->getSignalement();
        $fileList = $signalementFileProcessor->process(self::FILE_LIST['photos']);
        $signalementFileProcessor->addFilesToSignalement($fileList, $signalement);
        $this->assertNotNull($signalement->getFiles());
    }
}
