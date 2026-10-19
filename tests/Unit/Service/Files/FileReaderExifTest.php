<?php

namespace App\Tests\Unit\Service\Files;

use App\Entity\File;
use App\Service\Files\FileReaderExif;
use App\Service\Files\ImageVariantProvider;
use Intervention\Image\ImageManager;
use Intervention\Image\Interfaces\ImageInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\File\File as HttpFile;

class FileReaderExifTest extends TestCase
{
    private ImageManager&MockObject $imageManager;
    private ImageVariantProvider&MockObject $imageVariantProvider;
    private LoggerInterface&MockObject $logger;
    private FileReaderExif $fileReaderExif;

    protected function setUp(): void
    {
        $this->imageManager = $this->createMock(ImageManager::class);
        $this->imageVariantProvider = $this->createMock(ImageVariantProvider::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->fileReaderExif = new FileReaderExif(
            $this->imageManager,
            $this->imageVariantProvider,
            $this->logger
        );
    }

    public function testSetDatePriseDeVueWithoutFractions(): void
    {
        $currentYear = (int) date('Y');
        $exifDate = sprintf('%d:10:14 11:03:51', $currentYear);

        $file = $this->createFileWithExifData('test.jpg', $exifDate);
        $this->fileReaderExif->setDatePriseDeVueFromExifData($file);

        $this->assertInstanceOf(\DateTimeImmutable::class, $file->getDatePriseDeVue());
        $this->assertEquals(sprintf('%d-10-14 11:03:51', $currentYear), $file->getDatePriseDeVue()->format('Y-m-d H:i:s'));
    }

    public function testSetDatePriseDeVueWithMilliseconds(): void
    {
        $currentYear = (int) date('Y');
        $exifDate = sprintf('%d:10:14 11:03:51.989', $currentYear);

        $file = $this->createFileWithExifData('test.jpg', $exifDate);
        $this->fileReaderExif->setDatePriseDeVueFromExifData($file);

        $this->assertInstanceOf(\DateTimeImmutable::class, $file->getDatePriseDeVue());
        $this->assertEquals(sprintf('%d-10-14 11:03:51.989000', $currentYear), $file->getDatePriseDeVue()->format('Y-m-d H:i:s.u'));
    }

    public function testSetDatePriseDeVueWithMicroseconds(): void
    {
        $currentYear = (int) date('Y');
        $exifDate = sprintf('%d:10:14 11:03:51.123456', $currentYear);

        $file = $this->createFileWithExifData('test.jpg', $exifDate);
        $this->fileReaderExif->setDatePriseDeVueFromExifData($file);

        $this->assertInstanceOf(\DateTimeImmutable::class, $file->getDatePriseDeVue());
        $this->assertEquals(sprintf('%d-10-14 11:03:51.123456', $currentYear), $file->getDatePriseDeVue()->format('Y-m-d H:i:s.u'));
    }

    #[DataProvider('provideImpossibleDates')]
    public function testSetDatePriseDeVueWithImpossibleDate(string $exifDate): void
    {
        $file = $this->createFileWithExifData('test.jpg', $exifDate);

        $this->logger->expects($this->once())
            ->method('error')
            ->with(
                $this->stringContains('Impossible de convertir la donnée EXIF DateTimeOriginal'),
                $this->callback(static function (array $context): bool {
                    return array_key_exists('file_id', $context)
                        && $context['exception'] instanceof \UnexpectedValueException;
                })
            );

        $this->fileReaderExif->setDatePriseDeVueFromExifData($file);

        $this->assertNull($file->getDatePriseDeVue());
    }

    public static function provideImpossibleDates(): \Generator
    {
        $currentYear = (int) date('Y');
        yield 'February 30 without fraction' => [sprintf('%d:02:30 11:03:51', $currentYear)];
        yield 'February 30 with milliseconds' => [sprintf('%d:02:30 11:03:51.989', $currentYear)];
        yield 'April 31' => [sprintf('%d:04:31 11:03:51', $currentYear)];
        yield 'Month 13' => [sprintf('%d:13:01 11:03:51', $currentYear)];
        yield 'Invalid string' => ['invalid date string'];
    }

    #[DataProvider('provideAbsentOrEmptyOrNonStringValues')]
    public function testSetDatePriseDeVueWithAbsentEmptyOrNonStringValues(mixed $exifDate): void
    {
        $file = $this->createFileWithExifData('test.jpg', $exifDate);

        $this->logger->expects($this->never())->method('error');

        $this->fileReaderExif->setDatePriseDeVueFromExifData($file);

        $this->assertNull($file->getDatePriseDeVue());
    }

    public static function provideAbsentOrEmptyOrNonStringValues(): \Generator
    {
        yield 'Null value' => [null];
        yield 'Empty string' => [''];
        yield 'Whitespace string' => ['   '];
    }

    #[DataProvider('provideInvalidYears')]
    public function testSetDatePriseDeVueWithYearLessThanOrEqualTo1970OrFuture(string $exifDate): void
    {
        $file = $this->createFileWithExifData('test.jpg', $exifDate);

        $this->logger->expects($this->never())->method('error');

        $this->fileReaderExif->setDatePriseDeVueFromExifData($file);

        $this->assertNull($file->getDatePriseDeVue());
    }

    public static function provideInvalidYears(): \Generator
    {
        $currentYear = (int) date('Y');
        yield 'Year 1970' => ['1970:01:01 00:00:00'];
        yield 'Year 1970 with milliseconds' => ['1970:01:01 00:00:00.123'];
        yield 'Year 1969' => ['1969:12:31 23:59:59'];
        yield 'Year 0001' => ['0001:01:01 00:00:00'];
        yield 'Future year' => [sprintf('%d:01:01 00:00:00', $currentYear + 1)];
        yield 'Future year with fraction' => [sprintf('%d:01:01 00:00:00.500', $currentYear + 1)];
    }

    public function testSetDatePriseDeVueOnImageDecodeFailure(): void
    {
        $file = new File()->setFilename('corrupted.jpg');
        $httpFile = $this->createMock(HttpFile::class);

        $this->imageVariantProvider->expects($this->once())
            ->method('getFileVariant')
            ->with('corrupted.jpg')
            ->willReturn($httpFile);

        $this->imageManager->expects($this->once())
            ->method('decode')
            ->with($httpFile)
            ->willThrowException(new \Exception('Decoding failed'));

        $this->logger->expects($this->once())
            ->method('error')
            ->with(
                $this->stringContains('Impossible de décoder l\'image pour le fichier')
            );

        $this->fileReaderExif->setDatePriseDeVueFromExifData($file);

        $this->assertNull($file->getDatePriseDeVue());
    }

    private function createFileWithExifData(string $filename, mixed $exifDate): File
    {
        $file = new File()->setFilename($filename);
        $httpFile = $this->createMock(HttpFile::class);
        $image = $this->createMock(ImageInterface::class);

        $this->imageVariantProvider->expects($this->once())
            ->method('getFileVariant')
            ->with($filename)
            ->willReturn($httpFile);

        $this->imageManager->expects($this->once())
            ->method('decode')
            ->with($httpFile)
            ->willReturn($image);

        $image->expects($this->once())
            ->method('exif')
            ->with('EXIF.DateTimeOriginal')
            ->willReturn($exifDate);

        return $file;
    }
}
