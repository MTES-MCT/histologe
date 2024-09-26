<?php

namespace App\Tests\Unit\Service\Esabora\Handler;

use App\Entity\Affectation;
use App\Entity\Enum\PartnerType;
use App\Manager\JobEventManager;
use App\Service\Interconnection\Esabora\AbstractEsaboraService;
use App\Service\Interconnection\Esabora\EsaboraManager;
use App\Service\Interconnection\Esabora\EsaboraSISHService;
use App\Service\Interconnection\Esabora\Handler\InterventionArreteServiceHandler;
use App\Service\Interconnection\Esabora\Response\DossierArreteSISHCollectionResponse;
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
        $this->esaboraSISHService = $this->createMock(EsaboraSISHService::class);
        $this->esaboraManager = $this->createMock(EsaboraManager::class);

        $this->handler = new InterventionArreteServiceHandler(
            $this->esaboraSISHService,
            $this->esaboraManager,
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
            ->willReturn(
                $dossierArreteCollection = new DossierArreteSISHCollectionResponse($responseEsabora, 200)
            );

        $this->esaboraManager = $this->createMock(EsaboraManager::class);
        $this->esaboraManager
            ->expects($this->once())
            ->method('createOrUpdateArrete')
            ->with($this->affectation, $dossierArreteCollection->getCollection()[0]);

        $this->handler = new InterventionArreteServiceHandler(
            $this->esaboraSISHService,
            $this->esaboraManager,
        );

        $this->handler->handle($this->affectation);
        $this->assertEquals(1, $this->handler->getCountSuccess());
        $this->assertEquals(0, $this->handler->getCountFailed());
    }

    public function testGetPriority(): void
    {
        $this->assertSame(2, $this->handler::getPriority());
    }

    public function testGetServiceName(): void
    {
        $this->assertEquals(
            AbstractEsaboraService::ACTION_SYNC_DOSSIER_ARRETE,
            $this->handler->getServiceName()
        );
    }
}
