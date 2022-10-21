<?php

namespace App\Tests\Functional\Service;

use App\Service\UploadHandlerService;
use League\Flysystem\FilesystemOperator;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\AsciiSlugger;
use Symfony\Component\String\Slugger\SluggerInterface;

class UploadHandlerServiceTest extends KernelTestCase
{
    public string $projectDir = '';
    public Filesystem $filesystem;
    public string $fixturesPath = '/src/DataFixtures/';
    public string $originalFilename = 'sample';
    public string $targetFilename = 'sample-target';
    public string $extension = '.png';

    protected function setUp(): void
    {
        self::bootKernel();
        $this->projectDir = static::getContainer()->getParameter('kernel.project_dir');
        $this->filesystem = static::getContainer()->get(Filesystem::class);

        $this->filesystem->copy(
            $this->projectDir.$this->fixturesPath.$this->originalFilename.$this->extension,
            $this->projectDir.$this->fixturesPath.$this->targetFilename.$this->extension
        );
    }

    public function testTemporaryFileUploaded(): void
    {
        /** @var ParameterBagInterface $parameterBag */
        $parameterBag = static::getContainer()->get(ParameterBagInterface::class);

        $sluggerMock = $this->createMock(SluggerInterface::class);
        $sluggerMock
            ->method('slug')
            ->willReturn((new AsciiSlugger())->slug($this->targetFilename));

        $uploadHandlerService = new UploadHandlerService(
            $this->createMock(FilesystemOperator::class),
            $parameterBag,
            $sluggerMock,
            $this->filesystem,
            $this->createMock(LoggerInterface::class)
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
        $this->assertFileExists($parameterBag->get('uploads_tmp_dir').$fileResult['file']);
    }

    public function testUploadBigFileShouldThrowsException(): void
    {
        /** @var ParameterBagInterface $parameterBag */
        $parameterBag = static::getContainer()->get(ParameterBagInterface::class);

        $sluggerMock = $this->createMock(SluggerInterface::class);
        $sluggerMock
            ->method('slug')
            ->willReturn((new AsciiSlugger())->slug($this->targetFilename));

        $uploadHandlerService = new UploadHandlerService(
            $this->createMock(FilesystemOperator::class),
            $parameterBag,
            $sluggerMock,
            $this->filesystem,
            $this->createMock(LoggerInterface::class)
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
}
