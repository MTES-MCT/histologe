<?php

namespace App\Tests\Unit\Service\Signalement;

use App\Entity\File;
use App\Factory\FileFactory;
use App\Service\Files\FilenameGenerator;
use App\Service\ImageManipulationHandler;
use App\Service\Security\FileScanner;
use App\Service\Signalement\SignalementFileProcessor;
use App\Service\UploadHandlerService;
use App\Tests\FixturesHelper;
use PHPUnit\Framework\MockObject\MockObject;
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

    private MockObject|UploadHandlerService $uploadHandlerService;
    private MockObject|LoggerInterface $logger;
    private MockObject|FilenameGenerator $filenameGenerator;
    private MockObject|FileFactory $fileFactory;
    private MockObject|ImageManipulationHandler $imageManipulationHandler;
    private MockObject|FileScanner $fileScanner;

    protected function setUp(): void
    {
        $this->uploadHandlerService = $this->createMock(UploadHandlerService::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->filenameGenerator = $this->createMock(FilenameGenerator::class);
        $this->fileFactory = $this->createMock(FileFactory::class);
        $this->imageManipulationHandler = $this->createMock(ImageManipulationHandler::class);
        $this->fileScanner = $this->createMock(FileScanner::class);
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

        $fileList = $signalementFileProcessor->process(self::FILE_LIST, 'documents');
        $this->assertTrue($signalementFileProcessor->isValid());
        $this->assertEmpty($signalementFileProcessor->getErrors());
        $this->assertCount(2, $fileList);

        foreach ($fileList as $fileItem) {
            $this->assertArrayHasKey('title', $fileItem);
            $this->assertArrayHasKey('date', $fileItem);
            $this->assertArrayHasKey('type', $fileItem);
            $this->assertEquals(File::FILE_TYPE_DOCUMENT, $fileItem['type']);
        }
    }

    public function testAddFilesToSignalement(): void
    {
        $this->uploadHandlerService
            ->expects($this->atLeast(1))
            ->method('moveFromBucketTempFolder')
            ->willReturn('sample-'.uniqid().'.pdf');

        $this->fileFactory
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
        $fileList = $signalementFileProcessor->process(self::FILE_LIST, 'photos');
        $signalementFileProcessor->addFilesToSignalement($fileList, $signalement); // TODO
        $this->assertNotNull($signalement->getFiles());
    }
}
