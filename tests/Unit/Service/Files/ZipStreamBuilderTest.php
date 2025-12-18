<?php

namespace App\Tests\Unit\Service\Files;

use App\Entity\File;
use App\Service\Files\ZipStreamBuilder;
use App\Service\UploadHandlerService;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use ZipStream\Exception\OverflowException;

final class ZipStreamBuilderTest extends TestCase
{
    private const string PHP_TEMP_STREAM = 'php://temp';

    private string $tmpDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tmpDir = rtrim(sys_get_temp_dir(), \DIRECTORY_SEPARATOR).\DIRECTORY_SEPARATOR.'zipstream_builder_tests'.\DIRECTORY_SEPARATOR;
        if (!is_dir($this->tmpDir) && !@mkdir($this->tmpDir, 0775, true) && !is_dir($this->tmpDir)) {
            self::fail(sprintf('Unable to create tmp test dir: %s', $this->tmpDir));
        }
    }

    protected function tearDown(): void
    {
        if (is_dir($this->tmpDir)) {
            foreach (glob($this->tmpDir.'zip_files_*') ?: [] as $f) {
                @unlink($f);
            }
            @rmdir($this->tmpDir);
        }

        parent::tearDown();
    }

    /**
     * @throws OverflowException
     */
    public function testCreateInitializesZipAndCreatesTempFile(): void
    {
        $stream = fopen(self::PHP_TEMP_STREAM, 'w+');
        self::assertIsResource($stream);

        $uploadHandler = $this->createMock(UploadHandlerService::class);
        $uploadHandler
            ->expects(self::once())
            ->method('openReadStream')
            ->with('foo.pdf')
            ->willReturn($stream);

        $logger = $this->createMock(LoggerInterface::class);

        $parameterBag = $this->createMock(ParameterBagInterface::class);
        $parameterBag
            ->method('get')
            ->with('uploads_tmp_dir')
            ->willReturn($this->tmpDir);

        $builder = new ZipStreamBuilder($uploadHandler, $parameterBag, $logger);

        $file = $this->createMock(File::class);
        $file->method('getFilename')->willReturn('foo.pdf');

        $zipPath = $builder
            ->create('signalement-123')
            ->add($file)
            ->close();

        self::assertIsString($zipPath);
        self::assertFileExists($zipPath);
        self::assertGreaterThan(0, filesize($zipPath), 'Zip file should not be empty.');
    }

    public function testCreateTwiceThrowsLogicException(): void
    {
        $uploadHandler = $this->createMock(UploadHandlerService::class);
        $logger = $this->createMock(LoggerInterface::class);

        $parameterBag = $this->createMock(ParameterBagInterface::class);
        $parameterBag
            ->method('get')
            ->with('uploads_tmp_dir')
            ->willReturn($this->tmpDir);

        $builder = new ZipStreamBuilder($uploadHandler, $parameterBag, $logger);

        $builder->create('signalement-123');

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('ZIP archive is already initialized');

        $builder->create('signalement-456');
    }

    public function testAddBeforeCreateThrowsLogicException(): void
    {
        $uploadHandler = $this->createMock(UploadHandlerService::class);
        $parameterBag = $this->createMock(ParameterBagInterface::class);
        $logger = $this->createMock(LoggerInterface::class);

        $builder = new ZipStreamBuilder($uploadHandler, $parameterBag, $logger);

        $file = $this->createMock(File::class);
        $file->method('getFilename')->willReturn('foo.pdf');

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('ZIP archive is not initialized. Call create() before add() or addMany()');

        $builder->add($file);
    }

    /**
     * @throws OverflowException
     */
    public function testCloseBeforeCreateThrowsLogicException(): void
    {
        $uploadHandler = $this->createMock(UploadHandlerService::class);
        $parameterBag = $this->createMock(ParameterBagInterface::class);
        $logger = $this->createMock(LoggerInterface::class);

        $builder = new ZipStreamBuilder($uploadHandler, $parameterBag, $logger);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('ZIP archive is not initialized. Call create() before add() or addMany()');

        $builder->close();
    }

    public function testAddClosesReturnedStream(): void
    {
        $stream = fopen(self::PHP_TEMP_STREAM, 'w+');
        self::assertIsResource($stream);

        $uploadHandler = $this->createMock(UploadHandlerService::class);
        $uploadHandler
            ->expects(self::once())
            ->method('openReadStream')
            ->with('foo.pdf')
            ->willReturn($stream);

        $parameterBag = $this->createMock(ParameterBagInterface::class);
        $parameterBag
            ->method('get')
            ->with('uploads_tmp_dir')
            ->willReturn($this->tmpDir);

        $logger = $this->createMock(LoggerInterface::class);

        $builder = new ZipStreamBuilder($uploadHandler, $parameterBag, $logger);

        $file = $this->createMock(File::class);
        $file->method('getFilename')->willReturn('foo.pdf');

        $builder
            ->create('signalement-123')
            ->add($file);

        self::assertFalse(is_resource($stream), 'Stream should be closed after add().');

        $zipPath = $builder->close();
        self::assertFileExists($zipPath);
    }

    /**
     * @throws OverflowException
     */
    public function testAddManyAddsAllFiles(): void
    {
        $stream1 = fopen(self::PHP_TEMP_STREAM, 'w+');
        $stream2 = fopen(self::PHP_TEMP_STREAM, 'w+');
        self::assertIsResource($stream1);
        self::assertIsResource($stream2);

        $uploadHandler = $this->createMock(UploadHandlerService::class);
        $uploadHandler
            ->expects(self::exactly(2))
            ->method('openReadStream')
            ->willReturnCallback(function (string $filename) use ($stream1, $stream2) {
                return match ($filename) {
                    'a.pdf' => $stream1,
                    'b.pdf' => $stream2,
                    default => null,
                };
            });

        $parameterBag = $this->createMock(ParameterBagInterface::class);
        $parameterBag
            ->method('get')
            ->with('uploads_tmp_dir')
            ->willReturn($this->tmpDir);

        $logger = $this->createMock(LoggerInterface::class);

        $builder = new ZipStreamBuilder($uploadHandler, $parameterBag, $logger);

        $fileA = $this->createMock(File::class);
        $fileA->method('getFilename')->willReturn('a.pdf');

        $fileB = $this->createMock(File::class);
        $fileB->method('getFilename')->willReturn('b.pdf');

        $zipPath = $builder
            ->create('signalement-123')
            ->addMany([$fileA, $fileB])
            ->close();

        self::assertFileExists($zipPath);
        self::assertGreaterThan(0, filesize($zipPath));
        self::assertFalse(is_resource($stream1));
        self::assertFalse(is_resource($stream2));
    }

    /**
     * @throws OverflowException
     */
    public function testCanCreateAgainAfterClose(): void
    {
        $stream1 = fopen(self::PHP_TEMP_STREAM, 'w+');
        self::assertIsResource($stream1);

        fwrite($stream1, 'content-1');
        rewind($stream1);

        $stream2 = fopen(self::PHP_TEMP_STREAM, 'w+');
        self::assertIsResource($stream2);

        fwrite($stream2, 'content-2');
        rewind($stream2);

        $uploadHandler = $this->createMock(UploadHandlerService::class);
        $uploadHandler
            ->expects(self::exactly(2))
            ->method('openReadStream')
            ->with('foo.pdf')
            ->willReturnOnConsecutiveCalls($stream1, $stream2);

        $parameterBag = $this->createMock(ParameterBagInterface::class);
        $parameterBag
            ->method('get')
            ->with('uploads_tmp_dir')
            ->willReturn($this->tmpDir);

        $logger = $this->createMock(LoggerInterface::class);

        $builder = new ZipStreamBuilder($uploadHandler, $parameterBag, $logger);

        $fileOne = $this->createMock(File::class);
        $fileOne->expects(self::once())->method('getFilename')->willReturn('foo.pdf');

        $fileTwo = $this->createMock(File::class);
        $fileTwo->expects(self::once())->method('getFilename')->willReturn('foo.pdf');

        $zip1 = $builder->create('one')->add($fileOne)->close();
        self::assertFileExists($zip1);

        $zip2 = $builder->create('two')->add($fileTwo)->close();
        self::assertFileExists($zip2);

        self::assertNotSame($zip1, $zip2);

        self::assertFalse(is_resource($stream1));
        self::assertFalse(is_resource($stream2));
    }
}
