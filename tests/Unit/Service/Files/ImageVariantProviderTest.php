<?php

namespace App\Tests\Unit\Service\Files;

use App\Service\Files\ImageVariantProvider;
use App\Service\ImageManipulationHandler;
use League\Flysystem\FilesystemException;
use League\Flysystem\FilesystemOperator;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Filesystem\Filesystem;

class ImageVariantProviderTest extends KernelTestCase
{
    private FilesystemOperator&MockObject $fileStorageMock;
    private ImageVariantProvider $provider;
    private Filesystem $filesystem;

    private string $bucketDir;
    private string $tmpDir;

    protected function setUp(): void
    {
        self::bootKernel();

        $this->fileStorageMock = $this->createMock(FilesystemOperator::class);

        /** @var ParameterBagInterface $parameterBag */
        $parameterBag = self::getContainer()->get(ParameterBagInterface::class);

        $this->bucketDir = $parameterBag->get('url_bucket');
        $this->tmpDir = $parameterBag->get('uploads_tmp_dir');

        $this->provider = new ImageVariantProvider($this->fileStorageMock, $parameterBag);
        $this->filesystem = new Filesystem();
    }

    /**
     * @dataProvider provideVariants
     *
     * @throws FilesystemException
     */
    public function testGetFileVariantDownloadsFromBucketAndWritesToTmp(?string $variant, string $expectedSuffix): void
    {
        $original = '2026/01/mon-super-fichier.png';

        $names = ImageManipulationHandler::getVariantNames($original);
        $thumb = $names[ImageManipulationHandler::SUFFIX_THUMB];
        $resize = $names[ImageManipulationHandler::SUFFIX_RESIZE];

        $expectedFilename = 'thumb' === $variant ? $thumb : $resize;

        $this->writeFile($this->bucketDir.'/'.$expectedFilename, 'content-'.$expectedSuffix);

        $this->fileStorageMock->method('fileExists')->willReturnCallback(
            fn (string $path) => in_array($path, [$thumb, $resize, $expectedFilename], true)
        );

        $file = $this->provider->getFileVariant($original, $variant);

        $this->assertStringEndsWith($expectedSuffix.'.png', $file->getFilename());
        $this->assertFileExists($this->tmpDir.$expectedFilename);
        $this->assertSame('content-'.$expectedSuffix, file_get_contents($this->tmpDir.$expectedFilename));
    }

    public function provideVariants(): \Generator
    {
        yield 'thumb' => ['thumb', ImageManipulationHandler::SUFFIX_THUMB];
        yield 'resize' => ['resize', ImageManipulationHandler::SUFFIX_RESIZE];
    }

    private function writeFile(string $path, string $content): void
    {
        $dir = dirname($path);
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
        file_put_contents($path, $content);
    }

    protected function tearDown(): void
    {
        $currentYear = date('Y');
        $this->filesystem->remove([
            $this->bucketDir.'/'.$currentYear,
            $this->tmpDir.$currentYear,
        ]);

        parent::tearDown();
    }
}
