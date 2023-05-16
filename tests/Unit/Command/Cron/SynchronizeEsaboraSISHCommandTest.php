<?php

namespace App\Tests\Unit\Command\Cron;

use App\Command\Cron\SynchronizeEsaboraSISHCommand;
use App\Entity\Enum\PartnerType;
use App\Manager\JobEventManager;
use App\Repository\AffectationRepository;
use App\Service\Esabora\EsaboraManager;
use App\Service\Esabora\EsaboraSISHService;
use App\Service\Esabora\Response\DossierStateSISHResponse;
use App\Service\Mailer\NotificationMailerRegistry;
use App\Tests\FixturesHelper;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Serializer\SerializerInterface;

class SynchronizeEsaboraSISHCommandTest extends KernelTestCase
{
    use FixturesHelper;
    public const PATH_MOCK = '/../../../../tools/wiremock/src/Resources/Esabora/sish/';

    public function testSyncDossierEsaboraSISH(): void
    {
        $kernel = self::bootKernel();
        $application = new Application($kernel);

        $filepath = __DIR__.self::PATH_MOCK.'ws_etat_dossier_sas/etat_importe.json';
        $responseEsabora = json_decode(file_get_contents($filepath), true);
        $dossierResponse = new DossierStateSISHResponse($responseEsabora, 200);

        $esaboraServiceMock = $this->createMock(EsaboraSISHService::class);
        $esaboraServiceMock
            ->expects($this->atLeast(1))
            ->method('getStateDossier')
            ->with($affectation = $this->getAffectation(PartnerType::ARS))
            ->willReturn($dossierResponse);

        $affectationRepositoryMock = $this->createMock(AffectationRepository::class);

        $collection = (new ArrayCollection());
        $collection->add($affectation);

        $affectationRepositoryMock
            ->expects($this->atLeast(2))
            ->method('findAffectationSubscribedToEsabora')
            ->willReturn($collection->toArray());

        $jobEventManagerMock = $this->createMock(JobEventManager::class);
        $jobEventManagerMock
            ->expects($this->once())
            ->method('createJobEvent');

        $serializerMock = $this->createMock(SerializerInterface::class);
        $notificationMailerRegistry = self::getContainer()->get(NotificationMailerRegistry::class);
        $parameterBag = self::getContainer()->get(ParameterBagInterface::class);

        $esaboraManagerMock = $this->createMock(EsaboraManager::class);

        $command = $application->add(new SynchronizeEsaboraSISHCommand(
            $esaboraServiceMock,
            $esaboraManagerMock,
            $jobEventManagerMock,
            $affectationRepositoryMock,
            $serializerMock,
            $notificationMailerRegistry,
            $parameterBag,
        ));

        $commandTester = new CommandTester($command);
        $commandTester->execute([]);
        $commandTester->getDisplay();

        $commandTester->assertCommandIsSuccessful();
    }
}
