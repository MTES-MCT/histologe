<?php

namespace App\Tests\Functional\Command\Cron;

use App\Command\Cron\ClearStorageOriginalFileCommand;
use App\Repository\FileRepository;
use App\Service\Mailer\NotificationMailerRegistry;
use League\Flysystem\FilesystemOperator;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class ClearStorageOriginalFileCommandTest extends KernelTestCase
{
    private ParameterBagInterface $parameterBag;
    private FileRepository $fileRepository;
    private MockObject&FilesystemOperator $fileStorage;
    private NotificationMailerRegistry $mailerRegistry;

    protected function setUp(): void
    {
        $this->parameterBag = self::getContainer()->getParameterBag();
        $this->fileRepository = self::getContainer()->get(FileRepository::class);
        $this->fileStorage = $this->createMock(FilesystemOperator::class);
        $this->mailerRegistry = self::getContainer()->get(NotificationMailerRegistry::class);
    }

    public function testExecuteTwice(): void
    {
        $command = new ClearStorageOriginalFileCommand(
            $this->parameterBag,
            $this->fileRepository,
            $this->fileStorage,
            $this->mailerRegistry,
        );
        $commandTester = new CommandTester($command);
        $commandTester->execute([]);

        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('129 files processed with 129 files not found', $output);
        $this->assertEquals(Command::SUCCESS, $commandTester->getStatusCode());

        $commandTester = new CommandTester($command);
        $commandTester->execute([]);

        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('0 files processed with 0 files not found', $output);
        $this->assertEquals(Command::SUCCESS, $commandTester->getStatusCode());
    }
}
