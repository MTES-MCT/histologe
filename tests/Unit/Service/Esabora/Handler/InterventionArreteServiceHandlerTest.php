<?php

namespace App\Tests\Unit\Service\Esabora\Handler;

use App\Entity\Affectation;
use App\Entity\Enum\PartnerType;
use App\Manager\JobEventManager;
use App\Service\Esabora\AbstractEsaboraService;
use App\Service\Esabora\EsaboraManager;
use App\Service\Esabora\EsaboraSISHService;
use App\Service\Esabora\Handler\InterventionArreteServiceHandler;
use App\Service\Esabora\Response\DossierArreteSISHCollectionResponse;
use App\Tests\FixturesHelper;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\SerializerInterface;

class InterventionArreteServiceHandlerTest extends TestCase
{
    use FixturesHelper;

    protected SerializerInterface $serializer;
    protected EsaboraSISHService $esaboraSISHService;
    protected EsaboraManager $esaboraManager;
    protected JobEventManager $jobEventManager;
    protected InterventionArreteServiceHandler $handler;
    protected Affectation $affectation;

    protected function setUp(): void
    {
        $filepath = __DIR__.'/../../../../../tools/wiremock/src/Resources/Esabora/sish/ws_arretes_dossier_sas.json';
        $responseEsabora = json_decode(file_get_contents($filepath), true);

        $this->serializer = $this->createMock(SerializerInterface::class);
        $this->esaboraSISHService = $this->createMock(EsaboraSISHService::class);
        $this->esaboraManager = $this->createMock(EsaboraManager::class);
        $this->jobEventManager = $this->createMock(JobEventManager::class);

        $this->handler = new InterventionArreteServiceHandler(
            $this->serializer,
            $this->esaboraSISHService,
            $this->esaboraManager,
            $this->jobEventManager,
        );
    }

    public function testHandle(): void
    {
        $filepath = __DIR__.'/../../../../../tools/wiremock/src/Resources/Esabora/sish/ws_arretes_dossier_sas.json';
        $responseEsabora = json_decode(file_get_contents($filepath), true);

        $this->serializer = $this->createMock(SerializerInterface::class);
        $this->esaboraSISHService = $this->createMock(EsaboraSISHService::class);
        $this->esaboraSISHService
            ->expects($this->once())
            ->method('getArreteDossier')
            ->with($this->affectation = $this->getAffectation(PartnerType::ARS))
            ->willReturn($dossierArreteCollection = new DossierArreteSISHCollectionResponse($responseEsabora, 200));

        $this->esaboraManager = $this->createMock(EsaboraManager::class);
        $this->esaboraManager
            ->expects($this->once())
            ->method('createOrUpdateArrete')
            ->with($this->affectation, $dossierArreteCollection->getCollection()[0]);

        $this->jobEventManager = $this->createMock(JobEventManager::class);
        $this->jobEventManager
            ->expects($this->once())
            ->method('createJobEvent');

        $this->handler = new InterventionArreteServiceHandler(
            $this->serializer,
            $this->esaboraSISHService,
            $this->esaboraManager,
            $this->jobEventManager,
        );

        $this->handler->handle($this->affectation);
    }

    public function testGetPriority(): void
    {
        $this->assertSame(2, $this->handler::getPriority());
    }

    public function testGetServiceName(): void
    {
        $this->assertEquals(AbstractEsaboraService::ACTION_SYNC_DOSSIER_ARRETE, $this->handler->getServiceName());
    }
}
