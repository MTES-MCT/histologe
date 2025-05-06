<?php

namespace App\Tests\Unit\Service\Esabora\Handler;

use App\Repository\PartnerRepository;
use App\Service\Interconnection\Esabora\EsaboraSISHService;
use App\Service\Interconnection\Esabora\Handler\DossierAdresseServiceHandler;
use App\Tests\FixturesHelper;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\SerializerInterface;

class DossierAdresseServiceHandlerTest extends TestCase
{
    use FixturesHelper;

    protected MockObject|EsaboraSISHService $esaboraSISHService;
    protected MockObject|SerializerInterface $serializer;
    protected MockObject|PartnerRepository $partnerRepository;

    protected function setUp(): void
    {
        $this->esaboraSISHService = $this->createMock(EsaboraSISHService::class);
        $this->serializer = $this->createMock(SerializerInterface::class);
        $this->partnerRepository = $this->createMock(PartnerRepository::class);
    }

    public function testHandle(): void
    {
        $this->esaboraSISHService
            ->expects($this->atLeast(1))
            ->method('pushAdresse')
            ->willReturn($this->getDossierSISHResponse('ws_dossier_adresse.json'));

        $handler = new DossierAdresseServiceHandler(
            $this->esaboraSISHService,
        );

        $dossierMessageSISH = $this->getDossierMessageSISH();
        $handler->handle($dossierMessageSISH);
    }

    public function testGetPriority(): void
    {
        $handler = new DossierAdresseServiceHandler(
            $this->esaboraSISHService,
        );

        $this->assertSame(3, $handler::getPriority());
    }
}
