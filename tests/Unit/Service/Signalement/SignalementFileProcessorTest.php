<?php

namespace App\Tests\Unit\Service\Signalement;

use App\Entity\File;
use App\Factory\FileFactory;
use App\Service\Files\FilenameGenerator;
use App\Service\Signalement\SignalementFileProcessor;
use App\Service\UploadHandlerService;
use App\Tests\FixturesHelper;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

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
    private MockObject|UrlGeneratorInterface $urlGenerator;
    private MockObject|FileFactory $fileFactory;

    protected function setUp(): void
    {
        $this->uploadHandlerService = $this->createMock(UploadHandlerService::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->filenameGenerator = $this->createMock(FilenameGenerator::class);
        $this->urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $this->fileFactory = $this->createMock(FileFactory::class);
    }

    public function testProcessUsagerDocument(): void
    {
        $this->uploadHandlerService
            ->expects($this->atLeast(1))
            ->method('uploadFromFilename')
            ->willReturn('sample-'.uniqid().'.pdf');

        $signalementFileProcessor = new SignalementFileProcessor(
            $this->uploadHandlerService,
            $this->logger,
            $this->filenameGenerator,
            $this->urlGenerator,
            $this->fileFactory
        );

        [$fileList, $descriptionList] = $signalementFileProcessor->process(self::FILE_LIST, 'documents');
        $this->assertTrue($signalementFileProcessor->isValid());
        $this->assertEmpty($signalementFileProcessor->getErrors());
        $this->assertCount(2, $fileList);
        $this->assertCount(2, $descriptionList);

        foreach ($fileList as $fileItem) {
            $this->assertArrayHasKey('title', $fileItem);
            $this->assertArrayHasKey('date', $fileItem);
            $this->assertArrayHasKey('type', $fileItem);
            $this->assertEquals(File::FILE_TYPE_DOCUMENT, $fileItem['type']);
        }

        foreach ($descriptionList as $descriptionItem) {
            $dom = new \DOMDocument();
            libxml_use_internal_errors(true);
            $dom->loadHTML($descriptionItem);
            libxml_use_internal_errors(false);
            $liTags = $dom->getElementsByTagName('li');
            $this->assertCount(1, $liTags);

            $liTag = $liTags->item(0);
            $aTags = $liTag->getElementsByTagName('a');
            $this->assertCount(1, $aTags);

            $aTag = $aTags->item(0);
            $this->assertEquals('fr-link', $aTag->getAttribute('class'));
            $this->assertEquals('_blank', $aTag->getAttribute('target'));
            $this->assertEquals('&t=___TOKEN___', $aTag->getAttribute('href'));
        }
    }

    public function testAddFilesToSignalement()
    {
        $this->uploadHandlerService
            ->expects($this->atLeast(1))
            ->method('uploadFromFilename')
            ->willReturn('sample-'.uniqid().'.pdf');

        $this->fileFactory
            ->expects($this->atLeast(1))
            ->method('createInstanceFrom')
            ->willReturn($this->getPhotoFile());

        $signalementFileProcessor = new SignalementFileProcessor(
            $this->uploadHandlerService,
            $this->logger,
            $this->filenameGenerator,
            $this->urlGenerator,
            $this->fileFactory
        );
        $signalement = $this->getSignalement();
        [$fileList, $descriptionList] = $signalementFileProcessor->process(self::FILE_LIST, 'photos');
        $this->assertNotEmpty($descriptionList);
        $signalementFileProcessor->addFilesToSignalement($fileList, $signalement);
        $this->assertNotNull($signalement->getFiles());
    }
}
