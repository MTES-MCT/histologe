<?php

namespace App\Tests\Unit\Service;

use App\Service\ImageManipulationHandler;
use Intervention\Image\ImageManager;
use Intervention\Image\Interfaces\EncodedImageInterface;
use Intervention\Image\Interfaces\ImageInterface;
use League\Flysystem\FilesystemOperator;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class ImageInterventionHandlerTest extends TestCase
{
    private MockObject&ParameterBagInterface $parameterBag;
    private MockObject&FilesystemOperator $fileStorage;
    private MockObject&ImageManager $imageManager;

    protected function setUp(): void
    {
        $this->parameterBag = $this->createMock(ParameterBagInterface::class);
        $this->fileStorage = $this->createMock(FilesystemOperator::class);
        $this->fileStorage
            ->expects($this->atLeast(0))
            ->method('write');

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
        $encodedMock = $this->createMock(EncodedImageInterface::class);
        $encodedMock->method('__toString')->willReturn('encoded-content');

        $imageMock = $this->createMock(ImageInterface::class);
        $imageMock->expects($this->once())
            ->method('scaleDown')
            ->with(1000, 1000)
            ->willReturnSelf();
        $imageMock->expects($this->once())
            ->method('encode')
            ->willReturn($encodedMock);

        $this->imageManager
            ->expects($this->once())
            ->method('decode')
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
        $encodedMock = $this->createMock(EncodedImageInterface::class);
        $encodedMock->method('__toString')->willReturn('encoded-content');

        $imageMock = $this->createMock(ImageInterface::class);
        $imageMock->expects($this->once())
            ->method('cover')
            ->with(400, 400)
            ->willReturnSelf();
        $imageMock->expects($this->once())
            ->method('encode')
            ->willReturn($encodedMock);

        $this->imageManager
            ->expects($this->once())
            ->method('decode')
            ->willReturn($imageMock);

        $imageManipulationHandler = new ImageManipulationHandler(
            $this->parameterBag,
            $this->fileStorage,
            $this->imageManager
        );

        $result = $imageManipulationHandler->thumbnail(__DIR__.'/../../files/sample.jpg');

        $this->assertInstanceOf(ImageManipulationHandler::class, $result);
    }

    public static function provideFile(): \Generator
    {
        yield 'sample.jpg is image' => [__DIR__.'/../../files/sample.jpg', true];
        yield 'sample.pdf is not image ' => [__DIR__.'/../../files/sample.pdf', false];
    }
}
