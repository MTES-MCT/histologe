<?php

namespace App\Tests\Unit\Service\Esabora\Handler;

use App\Entity\Affectation;
use App\Entity\Enum\PartnerType;
use App\Manager\JobEventManager;
use App\Service\Interconnection\Esabora\AbstractEsaboraService;
use App\Service\Interconnection\Esabora\EsaboraManager;
use App\Service\Interconnection\Esabora\EsaboraSISHService;
use App\Service\Interconnection\Esabora\Handler\InterventionArreteServiceHandler;
use App\Service\Interconnection\Esabora\Normalizer\ArreteSISHCollectionResponseNormalizer;
use App\Service\Interconnection\Esabora\Response\DossierArreteSISHCollectionResponse;
use App\Service\Interconnection\Esabora\Response\Model\DossierArreteSISH;
use App\Tests\FixturesHelper;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\SerializerInterface;

class InterventionArreteServiceHandlerTest extends TestCase
{
    use FixturesHelper;

    protected SerializerInterface&MockObject $serializer;
    protected EsaboraSISHService&MockObject $esaboraSISHService;
    protected EsaboraManager&MockObject $esaboraManager;
    protected ArreteSISHCollectionResponseNormalizer&MockObject $normalizer;
    protected JobEventManager $jobEventManager;
    protected InterventionArreteServiceHandler $handler;
    protected Affectation $affectation;

    protected function setUp(): void
    {
        $this->esaboraSISHService = $this->createMock(EsaboraSISHService::class);
        $this->esaboraManager = $this->createMock(EsaboraManager::class);
        $this->normalizer = $this->createMock(ArreteSISHCollectionResponseNormalizer::class);

        $this->handler = new InterventionArreteServiceHandler(
            $this->esaboraSISHService,
            $this->normalizer,
            $this->esaboraManager,
        );
    }

    public function testHandle(): void
    {
        $filepath = __DIR__.'/../../../../../tools/wiremock/src/Resources/Esabora/sish/ws_arretes_dossier_sas.json';
        $responseEsabora = json_decode((string) file_get_contents($filepath), true);

        $this->serializer = $this->createMock(SerializerInterface::class);
        $this->esaboraSISHService = $this->createMock(EsaboraSISHService::class);
        $this->esaboraSISHService
            ->expects($this->once())
            ->method('getArreteDossier')
            ->with($this->affectation = $this->getAffectation(PartnerType::ARS))
            ->willReturn(
                $dossierArreteCollection = new DossierArreteSISHCollectionResponse($responseEsabora, 200)
            );

        $originalItem = $dossierArreteCollection->getCollection()[0];

        $arreteOnly = new DossierArreteSISH([
            'keyDataList' => [null, $originalItem->getArreteId()],
            'columnDataList' => [
                $originalItem->getLogicielProvenance(),
                $originalItem->getReferenceDossier(),
                $originalItem->getDossNum(),
                $originalItem->getArreteDate(),
                $originalItem->getArreteNumero(),
                $originalItem->getArreteType(),
                null,
                null,
            ],
        ]);

        $this->esaboraManager = $this->createMock(EsaboraManager::class);
        $this->esaboraManager
            ->expects($this->exactly(2))
            ->method('createOrUpdateArrete')
            ->willReturnCallback(function (Affectation $affectation, DossierArreteSISH $item) use (&$calls) {
                $calls[] = [$affectation, $item];
            });

        $normalizedResponse = DossierArreteSISHCollectionResponse::fromCollection(
            [$arreteOnly, $originalItem],
            200,
            'Imported',
            null
        );

        $this->normalizer
            ->expects($this->once())
            ->method('normalize')
            ->with($dossierArreteCollection)
            ->willReturn($normalizedResponse);

        $this->handler = new InterventionArreteServiceHandler(
            $this->esaboraSISHService,
            $this->normalizer,
            $this->esaboraManager,
        );

        $this->handler->handle($this->affectation, $this->affectation->getSignalement()->getUuid());
        $this->assertCount(2, $calls);

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
