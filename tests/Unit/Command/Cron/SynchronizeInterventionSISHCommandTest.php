<?php

namespace App\Tests\Unit\Command\Cron;

use App\Command\Cron\SynchronizeInterventionSISHCommand;
use App\Entity\Enum\PartnerType;
use App\Manager\JobEventManager;
use App\Repository\AffectationRepository;
use App\Repository\JobEventRepository;
use App\Service\Interconnection\Esabora\EsaboraManager;
use App\Service\Interconnection\Esabora\Handler\InterventionArreteServiceHandler;
use App\Service\Interconnection\Esabora\Handler\InterventionVisiteServiceHandler;
use App\Service\Mailer\NotificationMailerRegistry;
use App\Tests\FixturesHelper;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Serializer\SerializerInterface;

class SynchronizeInterventionSISHCommandTest extends KernelTestCase
{
    use FixturesHelper;

    public function testSyncInterventionDossier(): void
    {
        $kernel = self::bootKernel();
        $application = new Application($kernel);

        $esaboraManagerMock = $this->createMock(EsaboraManager::class);
        $serializerMock = $this->createMock(SerializerInterface::class);
        $affectationRepositoryMock = $this->createMock(AffectationRepository::class);
        $jobEventRepositoryMock = $this->createMock(JobEventRepository::class);
        $jobEventRepositoryMock
            ->expects($this->once())
            ->method('getReportEsaboraAction')
            ->willReturn(['success_count' => 4, 'failed_count' => 2]);

        $jobEventManagerMock = $this->createMock(JobEventManager::class);
        $jobEventManagerMock->expects($this->once())->method('getRepository')->willReturn($jobEventRepositoryMock);

        $affectation = $this->getAffectation(PartnerType::ARS);
        $affectations = [
            [0 => $affectation, 'uuid' => $affectation->getUuid()],
        ];
        $affectationRepositoryMock
            ->expects($this->atLeast(1))
            ->method('findAffectationSubscribedToEsabora')
            ->willReturn($affectations);

        $parameterBag = self::getContainer()->get(ParameterBagInterface::class);
        $notificationMailerRegistry = self::getContainer()->get(NotificationMailerRegistry::class);

        $visiteHandler = $this->createMock(InterventionVisiteServiceHandler::class);
        $arreteHandler = $this->createMock(InterventionArreteServiceHandler::class);
        $command = $application->add(new SynchronizeInterventionSISHCommand(
            $esaboraManagerMock,
            $jobEventManagerMock,
            $affectationRepositoryMock,
            $serializerMock,
            $notificationMailerRegistry,
            $parameterBag,
            self::getContainer()->get('doctrine')->getManager(),
            [$visiteHandler, $arreteHandler],
        ));

        $commandTester = new CommandTester($command);
        $commandTester->execute([]);
        $commandTester->getDisplay();

        $commandTester->assertCommandIsSuccessful();
    }
}
