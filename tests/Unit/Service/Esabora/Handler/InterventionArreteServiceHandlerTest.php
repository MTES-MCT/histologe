<?php

namespace App\Tests\Unit\Service\Esabora\Handler;

use App\Entity\Affectation;
use App\Entity\Enum\PartnerType;
use App\Service\Interconnection\Esabora\AbstractEsaboraService;
use App\Service\Interconnection\Esabora\EsaboraManager;
use App\Service\Interconnection\Esabora\EsaboraSISHService;
use App\Service\Interconnection\Esabora\Handler\InterventionArreteServiceHandler;
use App\Service\Interconnection\Esabora\Normalizer\ArreteSISHCollectionResponseNormalizer;
use App\Service\Interconnection\Esabora\Response\DossierArreteSISHCollectionResponse;
use App\Service\Interconnection\Esabora\Response\Model\DossierArreteSISH;
use App\Tests\FixturesHelper;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;

class InterventionArreteServiceHandlerTest extends TestCase
{
    use FixturesHelper;

    protected EsaboraSISHService&Stub $esaboraSISHService;
    protected EsaboraManager&Stub $esaboraManager;
    protected ArreteSISHCollectionResponseNormalizer&Stub $normalizer;
    protected InterventionArreteServiceHandler $handler;
    protected Affectation $affectation;

    protected function setUp(): void
    {
        $this->esaboraSISHService = $this->createStub(EsaboraSISHService::class);
        $this->esaboraManager = $this->createStub(EsaboraManager::class);
        $this->normalizer = $this->createStub(ArreteSISHCollectionResponseNormalizer::class);

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

        $esaboraSISHService = $this->createMock(EsaboraSISHService::class);
        $esaboraSISHService
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

        $esaboraManager = $this->createMock(EsaboraManager::class);
        $esaboraManager
            ->expects($this->exactly(2))
            ->method('createOrUpdateArrete')
            ->willReturnCallback(static function (Affectation $affectation, DossierArreteSISH $item) use (&$calls) {
                $calls[] = [$affectation, $item];
            });

        $normalizedResponse = DossierArreteSISHCollectionResponse::fromCollection(
            [$arreteOnly, $originalItem],
            200,
            'Imported',
            null
        );

        $normalizer = $this->createMock(ArreteSISHCollectionResponseNormalizer::class);
        $normalizer
            ->expects($this->once())
            ->method('normalize')
            ->with($dossierArreteCollection)
            ->willReturn($normalizedResponse);

        $this->handler = new InterventionArreteServiceHandler(
            $esaboraSISHService,
            $normalizer,
            $esaboraManager,
        );

        $this->handler->handle($this->affectation, $this->affectation->getSignalement()->getUuid());
        $this->assertCount(2, $calls);
    }

    public function testPriority(): void
    {
        $attributes = (new \ReflectionClass(InterventionArreteServiceHandler::class))
            ->getAttributes(AsTaggedItem::class);

        $this->assertCount(1, $attributes);
        $this->assertSame(2, $attributes[0]->newInstance()->priority);
    }

    public function testGetServiceName(): void
    {
        $this->assertEquals(
            AbstractEsaboraService::ACTION_SYNC_DOSSIER_ARRETE,
            $this->handler->getServiceName()
        );
    }
}
