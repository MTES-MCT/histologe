<?php

namespace App\Tests\Unit\Command\Cron;

use App\Command\Cron\SynchronizeInterventionSISHCommand;
use App\Entity\Enum\PartnerType;
use App\Manager\JobEventManager;
use App\Repository\AffectationRepository;
use App\Service\Esabora\EsaboraManager;
use App\Service\Esabora\Handler\InterventionArreteServiceHandler;
use App\Service\Esabora\Handler\InterventionVisiteServiceHandler;
use App\Service\Mailer\NotificationMailerRegistry;
use App\Tests\FixturesHelper;
use Doctrine\Common\Collections\ArrayCollection;
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
        $jobEventManagerMock = $this->createMock(JobEventManager::class);
        $affectationRepositoryMock = $this->createMock(AffectationRepository::class);

        $collection = (new ArrayCollection([$this->getAffectation(PartnerType::ARS)]));
        $affectationRepositoryMock
            ->expects($this->atLeast(1))
            ->method('findAffectationSubscribedToEsabora')
            ->willReturn($collection->toArray());

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
            [$visiteHandler, $arreteHandler],
        ));

        $commandTester = new CommandTester($command);
        $commandTester->execute([]);
        $commandTester->getDisplay();

        $commandTester->assertCommandIsSuccessful();
    }
}
