<?php

namespace App\Tests\Unit\Command;

use App\Command\SynchronizeEsaboraCommand;
use App\Entity\Affectation;
use App\Entity\Partner;
use App\Entity\Signalement;
use App\Manager\AffectationManager;
use App\Manager\JobEventManager;
use App\Repository\AffectationRepository;
use App\Service\Esabora\DossierResponse;
use App\Service\Esabora\EsaboraService;
use App\Service\Mailer\NotificationService;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Serializer\SerializerInterface;

class SynchronizeEsaboraCommandTest extends KernelTestCase
{
    public function testSyncDossierEsabora(): void
    {
        $kernel = self::bootKernel();
        $application = new Application($kernel);

        $filepath = __DIR__.'/../../../tools/wiremock/src/Resources/Esabora/ws_etat_dossier_sas/etat_non_importe.json';
        $responseEsabora = json_decode(file_get_contents($filepath), true);
        $dossierResponse = new DossierResponse($responseEsabora, 200);
        $affectation = (new Affectation())->setSignalement(new Signalement())->setPartner(new Partner());

        $esaboraServiceMock = $this->createMock(EsaboraService::class);
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
        $notificationService = self::getContainer()->get(NotificationService::class);
        $parameterBag = self::getContainer()->get(ParameterBagInterface::class);

        $command = $application->add(new SynchronizeEsaboraCommand(
            $esaboraServiceMock,
            $affectationManagerMock,
            $jobEventManagerMock,
            $serializerMock,
            $notificationService,
            $parameterBag,
        ));

        $commandTester = new CommandTester($command);
        $commandTester->execute([]);
        $commandTester->getDisplay();

        $commandTester->assertCommandIsSuccessful();
    }
}
