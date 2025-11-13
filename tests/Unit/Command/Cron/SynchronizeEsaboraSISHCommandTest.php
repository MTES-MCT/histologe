<?php

namespace App\Tests\Unit\Command\Cron;

use App\Command\Cron\SynchronizeEsaboraSISHCommand;
use App\Entity\Enum\PartnerType;
use App\Repository\AffectationRepository;
use App\Service\Interconnection\Esabora\EsaboraManager;
use App\Service\Interconnection\Esabora\EsaboraSISHService;
use App\Service\Interconnection\Esabora\Response\DossierStateSISHResponse;
use App\Service\Mailer\NotificationMailerRegistry;
use App\Tests\FixturesHelper;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Serializer\SerializerInterface;

class SynchronizeEsaboraSISHCommandTest extends KernelTestCase
{
    use FixturesHelper;
    public const string PATH_MOCK = '/../../../../tools/wiremock/src/Resources/Esabora/sish/';

    public function testSyncDossierEsaboraSISH(): void
    {
        $kernel = self::bootKernel();
        $application = new Application($kernel);

        $filepath = __DIR__.self::PATH_MOCK.'ws_etat_dossier_sas/etat_importe.json';
        $responseEsabora = json_decode((string) file_get_contents($filepath), true);
        $dossierResponse = new DossierStateSISHResponse($responseEsabora, 200);

        /** @var EsaboraSISHService&MockObject $esaboraServiceMock */
        $esaboraServiceMock = $this->createMock(EsaboraSISHService::class);
        $esaboraServiceMock
            ->expects($this->atLeast(1))
            ->method('getStateDossier')
            ->with($affectation = $this->getAffectation(PartnerType::ARS))
            ->willReturn($dossierResponse);

        /** @var AffectationRepository&MockObject $affectationRepositoryMock */
        $affectationRepositoryMock = $this->createMock(AffectationRepository::class);

        $affectations = [
            ['affectation' => $affectation, 'signalement_uuid' => $affectation->getUuid()],
        ];

        $affectationRepositoryMock
            ->expects($this->atLeast(1))
            ->method('findAffectationSubscribedToEsabora')
            ->willReturn($affectations);

        /** @var SerializerInterface&MockObject $serializerMock */
        $serializerMock = $this->createMock(SerializerInterface::class);
        $notificationMailerRegistry = self::getContainer()->get(NotificationMailerRegistry::class);
        /** @var ParameterBagInterface $parameterBag */
        $parameterBag = self::getContainer()->get(ParameterBagInterface::class);

        /** @var EsaboraManager&MockObject $esaboraManagerMock */
        $esaboraManagerMock = $this->createMock(EsaboraManager::class);

        /** @var EntityManagerInterface $entityManager */
        $entityManager = self::getContainer()->get('doctrine')->getManager();
        $command = $application->add(new SynchronizeEsaboraSISHCommand(
            $esaboraServiceMock,
            $esaboraManagerMock,
            $affectationRepositoryMock,
            $serializerMock,
            $notificationMailerRegistry,
            $parameterBag,
            $entityManager,
        ));

        $commandTester = new CommandTester($command);
        $commandTester->execute([]);
        $commandTester->getDisplay();

        $commandTester->assertCommandIsSuccessful();
    }
}
