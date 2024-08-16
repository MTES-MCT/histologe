<?php

namespace App\Tests\Functional\Command\Cron;

use App\Command\Cron\ClearStorageTmpFolderCommand;
use League\Flysystem\DirectoryListing;
use League\Flysystem\FilesystemException;
use League\Flysystem\FilesystemOperator;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class ClearStorageTmpFolderCommandTest extends KernelTestCase
{
    private MockObject|FilesystemOperator $fileStorage;
    private MockObject|ParameterBagInterface $parameterBag;
    private MockObject|LoggerInterface $logger;

    protected function setUp(): void
    {
        $this->fileStorage = $this->createMock(FilesystemOperator::class);
        $this->parameterBag = self::getContainer()->getParameterBag();
        $this->logger = $this->createMock(LoggerInterface::class);
    }

    /**
     * @throws FilesystemException
     */
    public function testExecuteWithFilesToDelete(): void
    {
        $files = [
            ['type' => 'file', 'path' => 'tmp/file1.txt'],
            ['type' => 'file', 'path' => 'tmp/file2.txt'],
        ];

        $this->fileStorage->expects($this->once())
            ->method('listContents')
            ->with('tmp/')
            ->willReturn(new DirectoryListing($files));

        $this->fileStorage->method('lastModified')
            ->willReturnOnConsecutiveCalls(
                (new \DateTimeImmutable('- 180 days'))->getTimestamp(),
                (new \DateTimeImmutable('- 170 days'))->getTimestamp()
            );

        $this->fileStorage->expects($this->once())
            ->method('delete')
            ->with('tmp/file1.txt');

        $command = new ClearStorageTmpFolderCommand($this->fileStorage, $this->parameterBag, $this->logger);
        $commandTester = new CommandTester($command);
        $commandTester->execute([]);

        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('1 documents delete in tmp folder', $output);
        $this->assertEquals(Command::SUCCESS, $commandTester->getStatusCode());
    }
}
