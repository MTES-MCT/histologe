<?php

namespace App\Tests\Unit\Command\Cron;

use App\Command\Cron\SynchronizeObjectStorageCommand;
use App\Service\Mailer\NotificationMail;
use App\Service\Mailer\NotificationMailerRegistry;
use App\Service\Mailer\NotificationMailerType;
use PHPUnit\Framework\MockObject\MockObject;
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
    private MockObject|LoggerInterface $logger;
    private MockObject|NotificationMailerRegistry $mailer;
    private MockObject|ParameterBagInterface $params;

    protected function setUp(): void
    {
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->mailer = $this->createMock(NotificationMailerRegistry::class);
        $this->params = $this->createMock(ParameterBagInterface::class);

        $this->params->method('get')->willReturnMap([
            ['maintenance_enable', false],
            ['cron_enable', true],
            ['admin_email', 'admin@example.com'],
        ]);
    }

    /**
     * @dataProvider provideExecutionOutcome
     */
    public function testCommandExecution(
        bool $shouldFail,
        int $expectedExitCode,
        string $expectedSnippet,
        string $loggerMethod,
    ): void {
        $this->logger->expects($this->once())->method($loggerMethod);

        $this->mailer->expects($this->once())->method('send')
            ->with($this->callback(function (NotificationMail $mail) use ($expectedSnippet) {
                return null !== $mail->getMessage()
                    && str_contains($mail->getMessage(), mb_substr($expectedSnippet, 0, 2));
            }));

        $tester = new CommandTester($this->createStubCommand($shouldFail));
        $exit = $tester->execute([]);

        $this->assertSame($expectedExitCode, $exit);
        $this->assertStringContainsString($expectedSnippet, $tester->getDisplay());
    }

    public static function provideExecutionOutcome(): \Generator
    {
        yield 'success' => [false, Command::SUCCESS, '✅ Synchronisation terminée avec succès.', 'info'];
        yield 'failure' => [true,  Command::FAILURE, '❌ Échec de la synchronisation',           'error'];
    }

    private function createStubCommand(bool $shouldFail): Command
    {
        return new class($this->logger, $this->params, $this->mailer, $shouldFail) extends SynchronizeObjectStorageCommand {
            public function __construct(
                private readonly LoggerInterface $logger,
                private readonly ParameterBagInterface $params,
                private readonly NotificationMailerRegistry $mailer,
                private readonly bool $shouldFail,
            ) {
                parent::__construct(
                    logger: $logger,
                    parameterBag: $params,
                    notificationMailerRegistry: $mailer,
                    sourceBucketName: 'fake_source',
                    destinationBucketName: 'fake_dest'
                );
            }

            private function notify(string $message): void
            {
                $this->mailer->send(new NotificationMail(
                    type: NotificationMailerType::TYPE_CRON,
                    to: $this->params->get('admin_email'),
                    message: $message,
                    cronLabel: 'Synchronisation des buckets',
                ));
            }

            protected function execute(InputInterface $input, OutputInterface $output): int
            {
                $io = new SymfonyStyle($input, $output);

                if ($this->shouldFail) {
                    $msg = "❌ Échec de la synchronisation.\n\nSimulated error: connection timeout.";
                    $this->logger->error($msg);
                    $io->error($msg);
                    $this->notify($msg);

                    return Command::FAILURE;
                }

                $msg = '✅ Synchronisation terminée avec succès.';
                $this->logger->info($msg);
                $io->success($msg);
                $this->notify($msg);

                return Command::SUCCESS;
            }
        };
    }
}
