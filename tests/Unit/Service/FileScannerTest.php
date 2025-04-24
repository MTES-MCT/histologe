<?php

namespace App\Tests\Unit\Service;

use App\Service\Security\FileScanner;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Sineflow\ClamAV\DTO\ScannedFile;
use Sineflow\ClamAV\Scanner;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class FileScannerTest extends TestCase
{
    public function testIsCleanWithEmptyFilePath()
    {
        $scanner = $this->createMock(Scanner::class);
        $parameterBag = $this->createMock(ParameterBagInterface::class);
        $logger = $this->createMock(LoggerInterface::class);

        $fileScanner = new FileScanner($scanner, $parameterBag, $logger, true);
        $result = $fileScanner->isClean('');

        $this->assertFalse($result, 'Should return false for empty file path.');
    }

    public function testIsCleanWithCopyOption()
    {
        $scanner = $this->createMock(Scanner::class);
        $parameterBag = $this->createMock(ParameterBagInterface::class);
        $scannedFile = $this->createMock(ScannedFile::class);
        $logger = $this->createMock(LoggerInterface::class);

        $parameterBag
            ->expects($this->once())
            ->method('get')
            ->with('uploads_tmp_dir')
            ->willReturn('tmp/');

        $scannedFile
            ->expects($this->once())
            ->method('isClean')
            ->willReturn(true);

        $scanner
            ->expects($this->once())
            ->method('scan')
            ->with($this->callback(function ($copiedFilepath) {
                return str_starts_with($copiedFilepath, 'tmp/clamav_');
            }))
            ->willReturn($scannedFile);

        $fileScanner = new FileScanner($scanner, $parameterBag, $logger, true);

        $dummyFilePath = 'tmp/dummy.txt';
        file_put_contents($dummyFilePath, 'dummy content');

        $result = $fileScanner->isClean($dummyFilePath);

        unlink($dummyFilePath);

        $this->assertTrue($result, 'Should return true for a clean scanned file.');
    }
}
