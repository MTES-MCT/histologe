<?php

namespace App\Tests\Functional\Service;

use App\Service\Files\FilenameGenerator;
use App\Service\Files\HeicToJpegConverter;
use App\Service\UploadHandlerService;
use League\Flysystem\FilesystemOperator;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;

class UploadHandlerServiceTest extends KernelTestCase
{
    private FilesystemOperator $filesystemOperator;
    private ParameterBagInterface $parameterBag;
    private SluggerInterface $slugger;
    private LoggerInterface $logger;
    private HeicToJpegConverter $heicToJpegConverter;

    private string $projectDir = '';
    private string $fixturesPath = '/src/DataFixtures/Images/';
    private string $originalFilename = 'sample';
    private string $targetFilename = 'sample-target';
    private string $extension = '.png';

    protected function setUp(): void
    {
        self::bootKernel();
        $this->projectDir = static::getContainer()->getParameter('kernel.project_dir');
        $filesystem = static::getContainer()->get(Filesystem::class);

        $filesystem->copy(
            $this->projectDir.$this->fixturesPath.$this->originalFilename.$this->extension,
            $this->projectDir.$this->fixturesPath.$this->targetFilename.$this->extension
        );

        $this->filesystemOperator = $this->createMock(FilesystemOperator::class);
        $this->parameterBag = static::getContainer()->get(ParameterBagInterface::class);
        $this->slugger = $this->createMock(SluggerInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->heicToJpegConverter = $this->createMock(HeicToJpegConverter::class);
    }

    public function testTemporaryFileUploaded(): void
    {
        $this->slugger
            ->method('slug')
            ->willReturn((new AsciiSlugger())->slug($this->targetFilename));

        $uploadHandlerService = new UploadHandlerService(
            $this->filesystemOperator,
            $this->parameterBag,
            $this->slugger,
            $this->logger,
            $this->heicToJpegConverter,
        );

        $uploadFile = new UploadedFile(
            $this->projectDir.$this->fixturesPath.$this->targetFilename.$this->extension,
            $this->targetFilename,
            'image/png',
            null,
            true
        );

        $uploadHandler = $uploadHandlerService->toTempFolder($uploadFile);
        $this->assertInstanceOf(UploadHandlerService::class, $uploadHandler);
        $fileResult = $uploadHandler->getFile();
        $this->assertIsArray($fileResult);
        $this->assertArrayHasKey('file', $fileResult);
        $this->assertArrayHasKey('titre', $fileResult);
    }

    public function testUploadBigFileShouldThrowsException(): void
    {
        /** @var ParameterBagInterface $parameterBag */
        $parameterBag = static::getContainer()->get(ParameterBagInterface::class);

        $uploadHandlerService = new UploadHandlerService(
            $this->createMock(FilesystemOperator::class),
            $parameterBag,
            $this->createMock(LoggerInterface::class),
            $this->createMock(HeicToJpegConverter::class),
            $this->createMock(FilenameGenerator::class),
        );

        $uploadedFileMock = $this->createMock(UploadedFile::class);
        $uploadedFileMock
            ->expects($this->once())
            ->method('getSize')
            ->willReturn(20 * 1024 * 1024);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Le fichier dépasse 10 MB');
        $uploadHandlerService->uploadFromFile($uploadedFileMock, 'test.png');
    }

    public function testUploadFromFilename(): void
    {
        $this->heicToJpegConverter
            ->expects($this->once())
            ->method('convert')
            ->willReturn('sample.txt');

        $uploadHandlerService = new UploadHandlerService(
            $this->filesystemOperator,
            $this->parameterBag,
            $this->slugger,
            $this->logger,
            $this->heicToJpegConverter,
        );

        $filename = $uploadHandlerService->uploadFromFilename('sample.txt');
        $this->assertEquals('sample.txt', $filename);
    }

    public function testTemporaryFileUploaded(): void
    {
        /** @var ParameterBagInterface $parameterBag */
        $parameterBag = static::getContainer()->get(ParameterBagInterface::class);

        $filenameMock = $this->createMock(FilenameGenerator::class);
        $filenameMock
            ->method('generateSafeName')
            ->willReturn('test.jpg');

        $uploadHandlerService = new UploadHandlerService(
            $this->createMock(FilesystemOperator::class),
            $parameterBag,
            $this->createMock(LoggerInterface::class),
            $this->createMock(HeicToJpegConverter::class),
            $filenameMock,
        );

        $uploadFile = new UploadedFile(
            $this->projectDir.$this->fixturesPath.$this->targetFilename.$this->extension,
            $this->targetFilename,
            'image/png',
            null,
            true
        );

        $uploadHandler = $uploadHandlerService->toTempFolder($uploadFile);
        $this->assertInstanceOf(UploadHandlerService::class, $uploadHandler);
        $fileResult = $uploadHandler->getFile();
        $this->assertIsArray($fileResult);
        $this->assertArrayHasKey('file', $fileResult);
        $this->assertArrayHasKey('titre', $fileResult);
    }
}
