<?php

namespace App\Tests\Unit\Service\Esabora\Handler;

use App\Entity\Affectation;
use App\Entity\Enum\PartnerType;
use App\Manager\JobEventManager;
use App\Service\Interconnection\Esabora\AbstractEsaboraService;
use App\Service\Interconnection\Esabora\EsaboraManager;
use App\Service\Interconnection\Esabora\EsaboraSISHService;
use App\Service\Interconnection\Esabora\Handler\InterventionVisiteServiceHandler;
use App\Service\Interconnection\Esabora\Response\DossierVisiteSISHCollectionResponse;
use App\Service\Interconnection\Esabora\Response\Model\DossierVisiteSISH;
use App\Tests\FixturesHelper;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\SerializerInterface;

class InterventionVisiteServiceHandlerTest extends TestCase
{
    use FixturesHelper;

    protected SerializerInterface $serializer;
    protected EsaboraSISHService $esaboraSISHService;
    protected EsaboraManager $esaboraManager;
    protected JobEventManager $jobEventManager;
    protected InterventionVisiteServiceHandler $handler;
    protected ?Affectation $affectation = null;

    protected function setUp(): void
    {
        $this->esaboraSISHService = $this->createMock(EsaboraSISHService::class);
        $this->esaboraManager = $this->createMock(EsaboraManager::class);

        $this->handler = new InterventionVisiteServiceHandler(
            $this->esaboraSISHService,
            $this->esaboraManager,
        );
    }

    /**
     * @throws \Exception
     */
    public function testHandle(): void
    {
        $filepath = __DIR__.'/../../../../../tools/wiremock/src/Resources/Esabora/sish/ws_visites_dossier_sas.json';
        $responseEsabora = json_decode(file_get_contents($filepath), true);

        $this->serializer = $this->createMock(SerializerInterface::class);
        $this->esaboraSISHService = $this->createMock(EsaboraSISHService::class);
        $this->esaboraSISHService
            ->expects($this->exactly(1))
            ->method('getVisiteDossier')
            ->with($this->affectation = $this->getAffectation(PartnerType::ARS))
            ->willReturn(
                $dossierVisiteSISHCollectionResponse = new DossierVisiteSISHCollectionResponse(
                    $responseEsabora,
                    200
                )
            );

        $this->esaboraManager = $this->createMock(EsaboraManager::class);
        $this->esaboraManager
            ->expects($matcher = $this->exactly(2))
            ->method('createOrUpdateVisite')
            ->willReturnCallback(
                function (
                    Affectation $affectation,
                    DossierVisiteSISH $dossierVisiteSISH,
                ) use ($dossierVisiteSISHCollectionResponse, $matcher) {
                    $this->assertEquals($this->affectation, $affectation);
                    match ($matcher->getInvocationCount()) {
                        1 => $this->assertEquals(
                            $dossierVisiteSISHCollectionResponse->getCollection()[0],
                            $dossierVisiteSISH
                        ),
                        2 => $this->assertEquals(
                            $dossierVisiteSISHCollectionResponse->getCollection()[1],
                            $dossierVisiteSISH
                        ),
                        default => throw new \UnexpectedValueException('Unexpected invocation count'),
                    };
                }
            );

        $this->handler = new InterventionVisiteServiceHandler(
            $this->esaboraSISHService,
            $this->esaboraManager,
        );

        $this->handler->handle($this->affectation, $this->affectation->getSignalement()->getUuid());
        $this->assertEquals(1, $this->handler->getCountSuccess());
        $this->assertEquals(0, $this->handler->getCountFailed());
    }

    public function testGetPriority(): void
    {
        $this->assertEquals(1, $this->handler::getPriority());
    }

    public function testGetServiceName(): void
    {
        $this->assertEquals(
            AbstractEsaboraService::ACTION_SYNC_DOSSIER_VISITE,
            $this->handler->getServiceName()
        );
    }
}
