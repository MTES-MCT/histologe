<?php

namespace App\Tests\Unit\Service\Files;

use App\Service\Files\ImageVariantProvider;
use League\Flysystem\FilesystemException;
use League\Flysystem\FilesystemOperator;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class ImageVariantProviderTest extends KernelTestCase
{
    private FilesystemOperator&MockObject $fileStorageMock;
    private ImageVariantProvider $imageVariantProvider;

    protected function setUp(): void
    {
        $this->fileStorageMock = $this->createMock(FilesystemOperator::class);
        /** @var ParameterBagInterface $parameterBag */
        $parameterBag = self::getContainer()->get(ParameterBagInterface::class);

        $this->imageVariantProvider = new ImageVariantProvider(
            $this->fileStorageMock,
            $parameterBag
        );
    }

    /**
     * @throws FilesystemException
     *
     * @dataProvider provideImages
     */
    public function testGetFileVariantSuccessfullyRetrievesThumb(string $variant): void
    {
        $this->fileStorageMock
            ->method('fileExists')
            ->willReturn(true);

        $file = $this->imageVariantProvider->getFileVariant(__DIR__.'/../../../files/sample.png', $variant);
        $this->assertStringContainsString($variant, $file->getFilename(), 'The filename should contain the variant');
    }

    public function provideImages(): \Generator
    {
        yield 'Sample for thumb' => ['thumb'];
        yield 'Sample for resize' => ['resize'];
    }
}
