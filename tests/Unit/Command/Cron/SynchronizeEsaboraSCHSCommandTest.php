<?php

namespace App\Tests\Unit\Command\Cron;

use App\Command\Cron\SynchronizeEsaboraSCHSCommand;
use App\Entity\Affectation;
use App\Entity\Partner;
use App\Entity\Signalement;
use App\Entity\Suivi;
use App\Repository\AffectationRepository;
use App\Service\Interconnection\Esabora\EsaboraManager;
use App\Service\Interconnection\Esabora\EsaboraSCHSService;
use App\Service\Interconnection\Esabora\Response\DossierEventsSCHSCollectionResponse;
use App\Service\Interconnection\Esabora\Response\DossierStateSCHSResponse;
use App\Service\Mailer\NotificationMailerRegistry;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Serializer\SerializerInterface;

class SynchronizeEsaboraSCHSCommandTest extends KernelTestCase
{
    public const string PATH_MOCK = '/../../../../tools/wiremock/src/Resources/Esabora/schs/';

    public function testSyncDossierEsaboraSCHS(): void
    {
        $kernel = self::bootKernel();
        $application = new Application($kernel);

        $filepath = __DIR__.self::PATH_MOCK.'ws_etat_dossier_sas/etat_non_importe.json';
        $responseEsabora = json_decode((string) file_get_contents($filepath), true);
        $dossierResponse = new DossierStateSCHSResponse($responseEsabora, 200);

        $fileEventPath = __DIR__.self::PATH_MOCK.'ws_get_dossier_events.json';
        $responseEsaboraEvent = json_decode((string) file_get_contents($fileEventPath), true);
        $dossierEventResponse = new DossierEventsSCHSCollectionResponse($responseEsaboraEvent, 200);

        $affectation = (new Affectation())->setSignalement(new Signalement())->setPartner(new Partner());

        $esaboraServiceMock = $this->createMock(EsaboraSCHSService::class);
        $esaboraServiceMock
            ->expects($this->atLeast(1))
            ->method('getStateDossier')
            ->with($affectation)
            ->willReturn($dossierResponse);

        $esaboraServiceMock
            ->expects($this->atLeast(1))
            ->method('getDossierEvents')
            ->with($affectation)
            ->willReturn($dossierEventResponse);

        $affectationRepositoryMock = $this->createMock(AffectationRepository::class);

        $affectations = [
            ['affectation' => $affectation, 'signalement_uuid' => $affectation->getSignalement()->getUuid()],
        ];

        $affectationRepositoryMock
            ->expects($this->atLeast(1))
            ->method('findAffectationSubscribedToEsabora')
            ->willReturn($affectations);

        $serializerMock = $this->createMock(SerializerInterface::class);
        $notificationMailerRegistry = self::getContainer()->get(NotificationMailerRegistry::class);
        /** @var ParameterBagInterface $parameterBag */
        $parameterBag = self::getContainer()->get(ParameterBagInterface::class);

        $esaboraManagerMock = $this->createMock(EsaboraManager::class);

        $command = $application->add(new SynchronizeEsaboraSCHSCommand(
            $esaboraServiceMock,
            $esaboraManagerMock,
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
        $this->assertStringContainsString('Synchronized 4 new events with 0 files', $commandTester->getDisplay());
        $commandTester->assertCommandIsSuccessful();
    }
}
