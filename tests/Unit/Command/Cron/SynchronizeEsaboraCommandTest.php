<?php

namespace App\Tests\Unit\Command\Cron;

use App\Command\SynchronizeEsaboraSCHSCommand;
use App\Entity\Affectation;
use App\Entity\Partner;
use App\Entity\Signalement;
use App\Manager\AffectationManager;
use App\Manager\JobEventManager;
use App\Repository\AffectationRepository;
use App\Service\Esabora\EsaboraSCHSService;
use App\Service\Esabora\Response\DossierStateSCHSResponse;
use App\Service\Mailer\NotificationMailerRegistry;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Serializer\SerializerInterface;

class SynchronizeEsaboraCommandTest extends KernelTestCase
{
    public const PATH_MOCK = '/../../../../tools/wiremock/src/Resources/Esabora/ws_etat_dossier_sas/';

    public function testSyncDossierEsabora(): void
    {
        $kernel = self::bootKernel();
        $application = new Application($kernel);

        $filepath = __DIR__.self::PATH_MOCK.'etat_non_importe.json';
        $responseEsabora = json_decode(file_get_contents($filepath), true);
        $dossierResponse = new DossierStateSCHSResponse($responseEsabora, 200);
        $affectation = (new Affectation())->setSignalement(new Signalement())->setPartner(new Partner());

        $esaboraServiceMock = $this->createMock(EsaboraSCHSService::class);
        $esaboraServiceMock
            ->expects($this->atLeast(1))
            ->method('getStateDossier')
            ->with($affectation)
            ->willReturn($dossierResponse);

        $affectationRepositoryMock = $this->createMock(AffectationRepository::class);
        $affectationManagerMock = $this->createMock(AffectationManager::class);
        $affectationManagerMock
            ->expects($this->once())
            ->method('getRepository')
            ->willReturn($affectationRepositoryMock);

        $collection = (new ArrayCollection());
        $collection->add($affectation);

        $affectationRepositoryMock
            ->expects($this->once())
            ->method('findAffectationSubscribedToEsabora')
            ->willReturn($collection->toArray());

        $jobEventManagerMock = $this->createMock(JobEventManager::class);
        $jobEventManagerMock
            ->expects($this->once())
            ->method('createJobEvent');

        $serializerMock = $this->createMock(SerializerInterface::class);
        $notificationMailerRegistry = self::getContainer()->get(NotificationMailerRegistry::class);
        $parameterBag = self::getContainer()->get(ParameterBagInterface::class);

        $command = $application->add(new SynchronizeEsaboraSCHSCommand(
            $esaboraServiceMock,
            $affectationManagerMock,
            $jobEventManagerMock,
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
