<?php

namespace App\Tests\Unit\Service;

use App\Service\ImageManipulationHandler;
use App\Tests\MockableResizeImage;
use App\Tests\MockableThumbnailImage;
use Intervention\Image\ImageManager;
use League\Flysystem\FilesystemOperator;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\StreamInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class ImageInterventionHandlerTest extends TestCase
{
    private MockObject|ParameterBagInterface $parameterBag;
    private MockObject|FilesystemOperator $fileStorage;
    private MockObject|ImageManager $imageManager;

    protected function setUp(): void
    {
        $this->parameterBag = $this->createMock(ParameterBagInterface::class);
        $this->fileStorage = $this->createMock(FilesystemOperator::class);
        $this->fileStorage
            ->expects($this->atLeast(0))
            ->method('writeStream');

        $this->fileStorage
            ->expects($this->atLeast(0))
            ->method('readStream')
            ->willReturn(__DIR__.'/../../files/sample.jpg');

        $this->imageManager = $this->createMock(ImageManager::class);
    }

    /**
     * @throws \Throwable
     */
    public function testResize(): void
    {
        $manager = new ImageManager();
        $image = $manager->make(__DIR__.'/../../files/sample.jpg');

        $imageMock = $this->createMock(MockableResizeImage::class);
        $imageMock->expects($this->once())
            ->method('resize')
            ->with(1000, 1000, $this->isType('callable'))
            ->willReturn($image);

        $streamMock = $this->createMock(StreamInterface::class);
        $imageMock
            ->expects($this->once())
            ->method('stream')->willReturn($streamMock);

        $this->imageManager
            ->expects($this->once())
            ->method('make')
            ->with(__DIR__.'/../../files/sample.jpg')
            ->willReturn($imageMock);

        $imageManipulationHandler = new ImageManipulationHandler(
            $this->parameterBag,
            $this->fileStorage,
            $this->imageManager
        );

        $result = $imageManipulationHandler->resize(__DIR__.'/../../files/sample.jpg');

        $this->assertInstanceOf(ImageManipulationHandler::class, $result);
    }

    /**
     * @throws \Throwable
     */
    public function testThumbnail(): void
    {
        $manager = new ImageManager();
        $image = $manager->make(__DIR__.'/../../files/sample.jpg');

        $imageMock = $this->createMock(MockableThumbnailImage::class);
        $imageMock->expects($this->once())
            ->method('fit')
            ->with(400, 400)
            ->willReturn($image);

        $streamMock = $this->createMock(StreamInterface::class);
        $imageMock
            ->expects($this->once())
            ->method('stream')
            ->willReturn($streamMock);

        $this->parameterBag
            ->expects($this->once())
            ->method('get')
            ->with('bucket_tmp_dir')
            ->willReturn('/tmp');

        $this->imageManager
            ->expects($this->once())
            ->method('make')
            ->with(__DIR__.'/../../files/sample.jpg')
            ->willReturn($imageMock);

        $imageManipulationHandler = new ImageManipulationHandler(
            $this->parameterBag,
            $this->fileStorage,
            $this->imageManager
        );

        $result = $imageManipulationHandler->thumbnail(__DIR__.'/../../files/sample.jpg');

        $this->assertInstanceOf(ImageManipulationHandler::class, $result);
    }

    /**
     * @dataProvider provideFile
     */
    public function testIsImage(string $filepath, bool $result): void
    {
        $imageManipulationHandler = new ImageManipulationHandler(
            $this->parameterBag,
            $this->fileStorage,
            $this->imageManager
        );

        $this->assertEquals($result, $imageManipulationHandler->isImage($filepath));
    }

    public function provideFile(): \Generator
    {
        yield 'sample.jpg is image' => [__DIR__.'/../../files/sample.jpg', true];
        yield 'sample.pdf is not image ' => [__DIR__.'/../../files/sample.pdf', false];
    }
}
