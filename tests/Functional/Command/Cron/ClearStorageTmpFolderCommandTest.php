<?php

namespace App\Tests\Functional\Command\Cron;

use App\Command\Cron\ClearStorageTmpFolderCommand;
use App\Repository\FileRepository;
use App\Service\Mailer\NotificationMailerRegistry;
use App\Service\UploadHandlerService;
use League\Flysystem\DirectoryListing;
use League\Flysystem\FilesystemException;
use League\Flysystem\FilesystemOperator;
use League\Flysystem\StorageAttributes;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class ClearStorageTmpFolderCommandTest extends KernelTestCase
{
    private MockObject&FilesystemOperator $fileStorage;
    private ParameterBagInterface $parameterBag;
    private NotificationMailerRegistry $mailerRegistry;
    private MockObject&LoggerInterface $logger;
    private FileRepository $fileRepository;
    private UploadHandlerService $uploadHandlerService;

    protected function setUp(): void
    {
        $this->fileStorage = $this->createMock(FilesystemOperator::class);
        $this->parameterBag = self::getContainer()->get(ParameterBagInterface::class);
        $this->mailerRegistry = self::getContainer()->get(NotificationMailerRegistry::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->fileRepository = self::getContainer()->get(FileRepository::class);
        $this->uploadHandlerService = self::getContainer()->get(UploadHandlerService::class);
    }

    /**
     * @throws FilesystemException
     */
    public function testExecuteWithFilesToDelete(): void
    {
        $storageAttributeMock = $this->createMock(StorageAttributes::class);
        $storageAttributeMock->method('type')->willReturn('file');
        $storageAttributeMock->method('path')->willReturn('tmp/file1.txt');
        $storageAttributeMock->method('lastModified')->willReturn(strtotime('- 7 months'));

        $this->fileStorage->expects($this->once())
            ->method('listContents')
            ->with('tmp/')
            ->willReturn(new DirectoryListing([$storageAttributeMock]));

        $this->fileStorage->expects($this->once())
            ->method('delete')
            ->with('tmp/file1.txt');

        $command = new ClearStorageTmpFolderCommand(
            $this->fileStorage,
            $this->parameterBag,
            $this->mailerRegistry,
            $this->logger,
            $this->fileRepository,
            $this->uploadHandlerService,
        );
        $commandTester = new CommandTester($command);
        $commandTester->execute([]);

        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('1 exports ont été supprimés', $output);
        $this->assertStringContainsString('1 document(s) ont été supprimé(s) du repertoire /tmp', $output);
        $this->assertEquals(Command::SUCCESS, $commandTester->getStatusCode());
    }
}
