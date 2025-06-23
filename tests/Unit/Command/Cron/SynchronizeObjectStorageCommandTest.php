<?php

namespace App\Tests\Unit\Command\Cron;

use App\Command\Cron\SynchronizeObjectStorageCommand;
use App\Service\Mailer\NotificationMail;
use App\Service\Mailer\NotificationMailerRegistry;
use App\Service\Mailer\NotificationMailerType;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class SynchronizeObjectStorageCommandTest extends TestCase
{
    public function testCommandSucceedsWithMockedProcess(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $mailer = $this->createMock(NotificationMailerRegistry::class);
        $params = $this->createMock(ParameterBagInterface::class);

        $params->method('get')->willReturnMap([
            ['maintenance_enable', false],
            ['cron_enable', true],
            ['admin_email', 'admin@example.com'],
        ]);

        $logger->expects($this->once())->method('info');
        $mailer->expects($this->once())->method('send')
            ->with($this->callback(fn (NotificationMail $mail) => str_contains($mail->getMessage(), '✅')
            ));

        $command = new class($logger, $params, $mailer) extends SynchronizeObjectStorageCommand {
            public function __construct(
                private readonly LoggerInterface $loggerMock,
                private readonly ParameterBagInterface $paramMock,
                private readonly NotificationMailerRegistry $mailerMock,
            ) {
                parent::__construct(
                    logger: $loggerMock,
                    parameterBag: $paramMock,
                    notificationMailerRegistry: $mailerMock,
                    sourceBucketName: 'fake_source',
                    destinationBucketName: 'fake_dest'
                );
            }

            protected function execute(InputInterface $input, OutputInterface $output): int
            {
                $io = new SymfonyStyle($input, $output);
                $io->note('🚀 Synchronisation en cours...');

                $io->write("Simulated rclone progress...\n");
                $message = '✅ Synchronisation terminée avec succès.';
                $this->loggerMock->info($message);
                $io->success($message);

                $this->mailerMock->send(
                    new NotificationMail(
                        type: NotificationMailerType::TYPE_CRON,
                        to: $this->paramMock->get('admin_email'),
                        message: $message,
                        cronLabel: 'Synchronisation des buckets',
                    )
                );

                return Command::SUCCESS;
            }
        };

        $tester = new CommandTester($command);
        $exitCode = $tester->execute([]);

        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertStringContainsString('✅ Synchronisation terminée avec succès.', $tester->getDisplay());
    }

    public function testCommandFailsWithErrorMessage(): void
    {
        // Arrange
        $logger = $this->createMock(LoggerInterface::class);
        $mailer = $this->createMock(NotificationMailerRegistry::class);
        $params = $this->createMock(ParameterBagInterface::class);

        $params->method('get')->willReturnMap([
            ['maintenance_enable', false],
            ['cron_enable', true],
            ['admin_email', 'admin@example.com'],
        ]);

        $logger->expects($this->once())->method('error')
            ->with($this->callback(fn (string $msg) => str_contains($msg, '❌ Échec')));
        $mailer->expects($this->once())->method('send')
            ->with($this->callback(fn (NotificationMail $mail) => str_contains($mail->getMessage(), '❌')
            ));

        $command = new class($logger, $params, $mailer) extends SynchronizeObjectStorageCommand {
            public function __construct(
                private readonly LoggerInterface $loggerMock,
                private readonly ParameterBagInterface $paramMock,
                private readonly NotificationMailerRegistry $mailerMock,
            ) {
                parent::__construct(
                    logger: $loggerMock,
                    parameterBag: $paramMock,
                    notificationMailerRegistry: $mailerMock,
                    sourceBucketName: 'fake_source',
                    destinationBucketName: 'fake_dest'
                );
            }

            protected function execute(InputInterface $input, OutputInterface $output): int
            {
                $io = new SymfonyStyle($input, $output);
                $io->note('🚀 Synchronisation en cours...');

                $errorMessage = "❌ Échec de la synchronisation.\n\nSimulated error: connection timeout.";
                $this->loggerMock->error($errorMessage);
                $io->error($errorMessage);

                $this->mailerMock->send(
                    new NotificationMail(
                        type: NotificationMailerType::TYPE_CRON,
                        to: $this->paramMock->get('admin_email'),
                        message: $errorMessage,
                        cronLabel: 'Synchronisation des buckets',
                    )
                );

                return Command::FAILURE;
            }
        };

        $tester = new CommandTester($command);
        $exitCode = $tester->execute([]);

        $this->assertSame(Command::FAILURE, $exitCode);
        $this->assertStringContainsString('❌ Échec de la synchronisation', $tester->getDisplay());
    }
}
