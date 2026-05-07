<?php

namespace App\Tests\Unit\Command\Cron;

use App\Command\Cron\SynchronizeInterventionSISHCommand;
use App\Scheduler\Message\SyncEsaboraSISHInterventionMessage;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;

class SynchronizeInterventionSISHCommandTest extends KernelTestCase
{
    public function testSyncInterventionDossier(): void
    {
        $kernel = self::bootKernel();
        $application = new Application($kernel);

        /** @var MessageBusInterface&MockObject $messageBusMock */
        $messageBusMock = $this->createMock(MessageBusInterface::class);
        $messageBusMock->expects($this->once())
            ->method('dispatch')
            ->with($this->isInstanceOf(SyncEsaboraSISHInterventionMessage::class))
            ->willReturn(new Envelope(new SyncEsaboraSISHInterventionMessage()));

        /** @var ParameterBagInterface $parameterBag */
        $parameterBag = static::getContainer()->get(ParameterBagInterface::class);

        $command = $application->add(new SynchronizeInterventionSISHCommand(
            $messageBusMock,
            $parameterBag,
        ));

        $commandTester = new CommandTester($command);
        $commandTester->execute([]);
        $this->assertStringContainsString('SISH Intervention synchronization message dispatched.', $commandTester->getDisplay());
        $commandTester->assertCommandIsSuccessful();
    }
}
