<?php

namespace App\Tests\Functional\Service;

use App\Exception\File\EmptyFileException;
use App\Exception\File\MaxUploadSizeExceededException;
use App\Exception\File\UnsupportedFileFormatException;
use App\Repository\FileRepository;
use App\Service\Files\FilenameGenerator;
use App\Service\Files\TmpFileWriter;
use App\Service\UploadHandlerService;
use League\Flysystem\FilesystemException;
use League\Flysystem\FilesystemOperator;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class UploadHandlerServiceTest extends KernelTestCase
{
    private MockObject&FilesystemOperator $filesystemOperator;
    private ParameterBagInterface $parameterBag;
    private MockObject&LoggerInterface $logger;
    private MockObject&FilenameGenerator $filenameGenerator;
    private MockObject&FileRepository $fileRepository;
    private TmpFileWriter $tmpFileWriter;

    private string $projectDir = '';
    private string $fixturesPath = '/src/DataFixtures/Images/';
    private string $originalFilename = 'sample';
    private string $targetFilename = 'sample-target';
    private string $extension = '.png';

    protected function setUp(): void
    {
        self::bootKernel();
        $this->projectDir = static::getContainer()->getParameter('kernel.project_dir');
        /** @var Filesystem $filesystem */
        $filesystem = static::getContainer()->get(Filesystem::class);

        $filesystem->copy(
            $this->projectDir.$this->fixturesPath.$this->originalFilename.$this->extension,
            $this->projectDir.$this->fixturesPath.$this->targetFilename.$this->extension
        );

        $this->filesystemOperator = $this->createMock(FilesystemOperator::class);
        $this->parameterBag = static::getContainer()->get(ParameterBagInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->filenameGenerator = $this->createMock(FilenameGenerator::class);
        $this->fileRepository = $this->createMock(FileRepository::class);
        $this->tmpFileWriter = $this->createMock(TmpFileWriter::class);
    }

    /**
     * @throws UnsupportedFileFormatException
     * @throws MaxUploadSizeExceededException
     * @throws FilesystemException
     * @throws EmptyFileException
     */
    public function testTemporaryFileUploaded(): void
    {
        $uploadFile = new UploadedFile(
            $this->projectDir.$this->fixturesPath.$this->targetFilename.$this->extension,
            $this->targetFilename,
            'image/png',
            null,
            true
        );

        $this->filenameGenerator
            ->expects($this->once())
            ->method('generate')
            ->with($uploadFile)
            ->willReturn('sample-target-649eb0a54a822.png');

        $this->filenameGenerator
            ->expects($this->once())
            ->method('getTitle')
            ->willReturn('sample-target.png');

        $uploadHandlerService = new UploadHandlerService(
            $this->filesystemOperator,
            $this->parameterBag,
            $this->logger,
            $this->filenameGenerator,
            $this->fileRepository,
            $this->tmpFileWriter
        );

        $fileResult = $uploadHandlerService->toTempFolder($uploadFile);
        $this->assertIsArray($fileResult);
        $this->assertArrayHasKey('file', $fileResult);
        $this->assertArrayHasKey('titre', $fileResult);
        $this->assertNotEmpty($fileResult['file']);
        $this->assertNotEmpty($fileResult['titre']);
    }

    public function testUploadBigFileShouldThrowsException(): void
    {
        /** @var ParameterBagInterface $parameterBag */
        $parameterBag = static::getContainer()->get(ParameterBagInterface::class);
        /** @var MockObject&FilesystemOperator $fileSystem */
        $fileSystem = $this->createMock(FilesystemOperator::class);
        /** @var MockObject&FilenameGenerator $fileName */
        $fileName = $this->createMock(FilenameGenerator::class);
        /** @var MockObject&LoggerInterface $logger */
        $logger = $this->createMock(LoggerInterface::class);
        $uploadHandlerService = new UploadHandlerService(
            $fileSystem,
            $parameterBag,
            $logger,
            $fileName,
            $this->fileRepository,
            $this->tmpFileWriter
        );

        /** @var MockObject&UploadedFile $uploadedFileMock */
        $uploadedFileMock = $this->createMock(UploadedFile::class);
        $uploadedFileMock
            ->expects($this->exactly(2))
            ->method('getSize')
            ->willReturn(20 * 1024 * 1024);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Le fichier dépasse 10 MB');
        $uploadHandlerService->uploadFromFile($uploadedFileMock, 'test.png');
    }

    public function testUploadVideoFileShouldThrowsException(): void
    {
        /** @var ParameterBagInterface $parameterBag */
        $parameterBag = static::getContainer()->get(ParameterBagInterface::class);
        /** @var MockObject&FilesystemOperator $fileSystem */
        $fileSystem = $this->createMock(FilesystemOperator::class);
        /** @var MockObject&FilenameGenerator $fileName */
        $fileName = $this->createMock(FilenameGenerator::class);
        /** @var MockObject&LoggerInterface $logger */
        $logger = $this->createMock(LoggerInterface::class);
        $uploadHandlerService = new UploadHandlerService(
            $fileSystem,
            $parameterBag,
            $logger,
            $fileName,
            $this->fileRepository,
            $this->tmpFileWriter
        );

        /** @var MockObject&UploadedFile $uploadedFileMock */
        $uploadedFileMock = $this->createMock(UploadedFile::class);
        $uploadedFileMock
            ->expects($this->atLeast(1))
            ->method('getMimeType')
            ->willReturn('video/webm');
        $uploadedFileMock
            ->expects($this->atLeast(1))
            ->method('getClientOriginalExtension')
            ->willReturn('webm');

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Les fichiers de format video/webm ne sont pas pris en charge, merci de choisir un fichier au format '.UploadHandlerService::getAcceptedExtensions('document'));
        $uploadHandlerService->uploadFromFile($uploadedFileMock, 'test.webm');
    }

    /**
     * @throws UnsupportedFileFormatException
     * @throws MaxUploadSizeExceededException
     * @throws FilesystemException
     * @throws EmptyFileException
     */
    public function testUploadToTempFolderThrowException(): void
    {
        $uploadFile = new UploadedFile(
            $this->projectDir.$this->fixturesPath.$this->targetFilename.$this->extension,
            $this->targetFilename,
            'image/png',
            null,
            true
        );

        $this->filesystemOperator
        ->expects($this->once())
        ->method('writeStream')
        ->willThrowException(new FileException());

        $uploadHandlerService = new UploadHandlerService(
            $this->filesystemOperator,
            $this->parameterBag,
            $this->logger,
            $this->filenameGenerator,
            $this->fileRepository,
            $this->tmpFileWriter
        );

        $uploadHandler = $uploadHandlerService->toTempFolder($uploadFile);
        $this->assertIsArray($uploadHandler);
        $this->assertArrayHasKey('error', $uploadHandler);
    }
}
