<?php

namespace App\Tests\Unit\Service\Files;

use App\Entity\File;
use App\Entity\Signalement;
use App\Entity\User;
use App\Factory\FileFactory;
use App\Service\Files\SignalementFileAttacher;
use App\Service\Security\FileScanner;
use App\Service\UploadHandlerService;
use PHPUnit\Framework\TestCase;

class SignalementFileAttacherTest extends TestCase
{
    public function testCreateAndAttachAttachesFileAndHydratesMetadata(): void
    {
        $signalement = $this->createMock(Signalement::class);
        $user = $this->createMock(User::class);
        $file = $this->createMock(File::class);

        $fileData = [
            'file' => 'document.pdf',
            'titre' => 'Test Document',
            'slug' => 'test',
        ];

        $fileFactory = $this->createMock(FileFactory::class);
        $uploadHandlerService = $this->createMock(UploadHandlerService::class);
        $fileScanner = $this->createMock(FileScanner::class);

        $fileFactory
            ->expects(self::once())
            ->method('createFromFileArray')
            ->with(file: $fileData, signalement: $signalement)
            ->willReturn($file);

        $file
            ->expects(self::exactly(3))
            ->method('getFilename')
            ->willReturn('document.pdf');

        $uploadHandlerService
            ->expects(self::once())
            ->method('moveFromBucketTempFolder')
            ->with('document.pdf');

        $uploadHandlerService
            ->expects(self::once())
            ->method('getFileSize')
            ->with('document.pdf')
            ->willReturn(12345);

        $file
            ->expects(self::once())
            ->method('setSize')
            ->with('12345');

        $uploadHandlerService
            ->expects(self::once())
            ->method('hasVariants')
            ->with('document.pdf')
            ->willReturn(true);

        $file
            ->expects(self::once())
            ->method('setIsVariantsGenerated')
            ->with(true);

        $file
            ->expects(self::once())
            ->method('setUploadedBy')
            ->with($user);

        $file
            ->expects(self::never())
            ->method('setScannedAt');

        $file
            ->expects(self::never())
            ->method('setIsSuspicious');

        $signalement
            ->expects(self::once())
            ->method('addFile')
            ->with($file);

        $attacher = new SignalementFileAttacher(
            $fileFactory,
            $uploadHandlerService,
            $fileScanner,
            false,
        );

        $attacher->createAndAttach($signalement, $fileData, $user);
    }

    public function testCreateAndAttachScansPdfAndMarksAsNotSuspiciousWhenClean(): void
    {
        $signalement = $this->createMock(Signalement::class);
        $file = $this->createMock(File::class);

        $fileData = [
            'file' => 'document.pdf',
            'titre' => 'Test Document',
            'slug' => 'test',
        ];

        $fileFactory = $this->createMock(FileFactory::class);
        $uploadHandlerService = $this->createMock(UploadHandlerService::class);
        $fileScanner = $this->createMock(FileScanner::class);

        $fileFactory
            ->expects(self::once())
            ->method('createFromFileArray')
            ->willReturn($file);

        $file
            ->expects(self::exactly(5))
            ->method('getFilename')
            ->willReturn('document.pdf');

        $uploadHandlerService
            ->expects(self::once())
            ->method('moveFromBucketTempFolder')
            ->with('document.pdf');

        $uploadHandlerService
            ->expects(self::once())
            ->method('getFileSize')
            ->with('document.pdf')
            ->willReturn(500);

        $file
            ->expects(self::once())
            ->method('setSize')
            ->with('500');

        $uploadHandlerService
            ->expects(self::once())
            ->method('hasVariants')
            ->with('document.pdf')
            ->willReturn(false);

        $file
            ->expects(self::once())
            ->method('setIsVariantsGenerated')
            ->with(false);

        $file
            ->expects(self::once())
            ->method('setScannedAt')
            ->with(self::isInstanceOf(\DateTimeImmutable::class));

        $uploadHandlerService
            ->expects(self::once())
            ->method('getTmpFilepath')
            ->with('document.pdf')
            ->willReturn('/tmp/document.pdf');

        $fileScanner
            ->expects(self::once())
            ->method('isClean')
            ->with('/tmp/document.pdf')
            ->willReturn(true);

        $file
            ->expects(self::never())
            ->method('setIsSuspicious');

        $signalement
            ->expects(self::once())
            ->method('addFile')
            ->with($file);

        $attacher = new SignalementFileAttacher(
            $fileFactory,
            $uploadHandlerService,
            $fileScanner,
            true,
        );

        $attacher->createAndAttach($signalement, $fileData);
    }

    public function testCreateAndAttachScansPdfAndMarksAsSuspiciousWhenNotClean(): void
    {
        $signalement = $this->createMock(Signalement::class);
        $file = $this->createMock(File::class);

        $fileData = [
            'file' => 'infected.pdf',
            'titre' => 'Infected Document',
            'slug' => 'test',
        ];

        $fileFactory = $this->createMock(FileFactory::class);
        $uploadHandlerService = $this->createMock(UploadHandlerService::class);
        $fileScanner = $this->createMock(FileScanner::class);

        $fileFactory
            ->expects(self::once())
            ->method('createFromFileArray')
            ->willReturn($file);

        $file
            ->expects(self::exactly(5))
            ->method('getFilename')
            ->willReturn('infected.pdf');

        $uploadHandlerService
            ->expects(self::once())
            ->method('moveFromBucketTempFolder')
            ->with('infected.pdf');

        $uploadHandlerService
            ->expects(self::once())
            ->method('getFileSize')
            ->with('infected.pdf')
            ->willReturn(null);

        $file
            ->expects(self::once())
            ->method('setSize')
            ->with(null);

        $uploadHandlerService
            ->expects(self::once())
            ->method('hasVariants')
            ->with('infected.pdf')
            ->willReturn(false);

        $file
            ->expects(self::once())
            ->method('setIsVariantsGenerated')
            ->with(false);

        $file
            ->expects(self::once())
            ->method('setScannedAt')
            ->with(self::isInstanceOf(\DateTimeImmutable::class));

        $uploadHandlerService
            ->expects(self::once())
            ->method('getTmpFilepath')
            ->with('infected.pdf')
            ->willReturn('/tmp/infected.pdf');

        $fileScanner
            ->expects(self::once())
            ->method('isClean')
            ->with('/tmp/infected.pdf')
            ->willReturn(false);

        $file
            ->expects(self::once())
            ->method('setIsSuspicious')
            ->with(true);

        $signalement
            ->expects(self::once())
            ->method('addFile')
            ->with($file);

        $attacher = new SignalementFileAttacher(
            $fileFactory,
            $uploadHandlerService,
            $fileScanner,
            true,
        );

        $attacher->createAndAttach($signalement, $fileData);
    }

    public function testCreateAndAttachDoesNotScanNonPdfFile(): void
    {
        $signalement = $this->createMock(Signalement::class);
        $file = $this->createMock(File::class);

        $fileData = [
            'file' => 'photo.jpg',
            'slug' => 'test',
            'titre' => 'Photo',
        ];

        $fileFactory = $this->createMock(FileFactory::class);
        $uploadHandlerService = $this->createMock(UploadHandlerService::class);
        $fileScanner = $this->createMock(FileScanner::class);

        $fileFactory
            ->expects(self::once())
            ->method('createFromFileArray')
            ->willReturn($file);

        $file
            ->expects(self::exactly(4))
            ->method('getFilename')
            ->willReturn('photo.jpg');

        $uploadHandlerService
            ->expects(self::once())
            ->method('moveFromBucketTempFolder')
            ->with('photo.jpg');

        $uploadHandlerService
            ->expects(self::once())
            ->method('getFileSize')
            ->with('photo.jpg')
            ->willReturn(200);

        $file
            ->expects(self::once())
            ->method('setSize')
            ->with('200');

        $uploadHandlerService
            ->expects(self::once())
            ->method('hasVariants')
            ->with('photo.jpg')
            ->willReturn(true);

        $file
            ->expects(self::once())
            ->method('setIsVariantsGenerated')
            ->with(true);

        $file
            ->expects(self::once())
            ->method('setScannedAt')
            ->with(self::isInstanceOf(\DateTimeImmutable::class));

        $uploadHandlerService
            ->expects(self::never())
            ->method('getTmpFilepath');

        $fileScanner
            ->expects(self::never())
            ->method('isClean');

        $file
            ->expects(self::never())
            ->method('setIsSuspicious');

        $signalement
            ->expects(self::once())
            ->method('addFile')
            ->with($file);

        $attacher = new SignalementFileAttacher(
            $fileFactory,
            $uploadHandlerService,
            $fileScanner,
            true,
        );

        $attacher->createAndAttach($signalement, $fileData);
    }
}
