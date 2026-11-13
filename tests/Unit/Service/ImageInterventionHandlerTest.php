<?php

namespace App\Tests\Unit\Service;

use App\Service\Files\ImageManipulationHandler;
use Intervention\Image\ImageManager;
use Intervention\Image\Interfaces\EncodedImageInterface;
use Intervention\Image\Interfaces\ImageInterface;
use League\Flysystem\FilesystemOperator;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class ImageInterventionHandlerTest extends TestCase
{
    private Stub&ParameterBagInterface $parameterBag;
    private Stub&FilesystemOperator $fileStorage;
    private MockObject&ImageManager $imageManager;

    protected function setUp(): void
    {
        $this->parameterBag = $this->createStub(ParameterBagInterface::class);
        $this->fileStorage = $this->createStub(FilesystemOperator::class);

        $this->imageManager = $this->createMock(ImageManager::class);
    }

    /**
     * @throws \Throwable
     */
    public function testResize(): void
    {
        $encodedStub = $this->createStub(EncodedImageInterface::class);
        $encodedStub->method('__toString')->willReturn('encoded-content');

        $imageMock = $this->createMock(ImageInterface::class);
        $imageMock->expects($this->once())
            ->method('scaleDown')
            ->with(1000, 1000)
            ->willReturnSelf();
        $imageMock->expects($this->once())
            ->method('encode')
            ->willReturn($encodedStub);

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
        $encodedStub = $this->createStub(EncodedImageInterface::class);
        $encodedStub->method('__toString')->willReturn('encoded-content');

        $imageMock = $this->createMock(ImageInterface::class);
        $imageMock->expects($this->once())
            ->method('cover')
            ->with(400, 400)
            ->willReturnSelf();
        $imageMock->expects($this->once())
            ->method('encode')
            ->willReturn($encodedStub);

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
}
