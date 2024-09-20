<?php

namespace App\Tests\Unit\Command\Cron;

use App\Command\Cron\SynchronizeEsaboraSCHSCommand;
use App\Entity\Affectation;
use App\Entity\Partner;
use App\Entity\Signalement;
use App\Entity\Suivi;
use App\Manager\JobEventManager;
use App\Repository\AffectationRepository;
use App\Service\Esabora\EsaboraManager;
use App\Service\Esabora\EsaboraSCHSService;
use App\Service\Esabora\Response\DossierStateSCHSResponse;
use App\Service\Mailer\NotificationMailerRegistry;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Serializer\SerializerInterface;

class SynchronizeEsaboraSCHSCommandTest extends KernelTestCase
{
    public const PATH_MOCK = '/../../../../tools/wiremock/src/Resources/Esabora/schs/ws_etat_dossier_sas/';

    public function testSyncDossierEsaboraSCHS(): void
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

        $collection = (new ArrayCollection());
        $collection->add($affectation);

        $affectationRepositoryMock
            ->expects($this->atLeast(1))
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

        $command = $application->add(new SynchronizeEsaboraSCHSCommand(
            $esaboraServiceMock,
            $esaboraManagerMock,
            $jobEventManagerMock,
            $affectationRepositoryMock,
            $serializerMock,
            $notificationMailerRegistry,
            $parameterBag,
            self::getContainer()->get('logger'),
            self::getContainer()->get('doctrine')->getRepository(Suivi::class),
            self::getContainer()->get('doctrine')->getManager(),
        ));

        $commandTester = new CommandTester($command);
        $commandTester->execute([]);
        $commandTester->getDisplay();

        $commandTester->assertCommandIsSuccessful();
    }
}
