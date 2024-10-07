<?php

namespace App\Tests\Unit\Service\Esabora\Handler;

use App\Manager\JobEventManager;
use App\Repository\PartnerRepository;
use App\Service\Interconnection\Esabora\EsaboraSISHService;
use App\Service\Interconnection\Esabora\Handler\DossierServiceHandler;
use App\Tests\FixturesHelper;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\SerializerInterface;

class DossierServiceHandlerTest extends TestCase
{
    use FixturesHelper;

    protected MockObject|EsaboraSISHService $esaboraSISHService;
    protected MockObject|SerializerInterface $serializer;
    protected MockObject|JobEventManager $jobEventManager;
    protected MockObject|PartnerRepository $partnerRepository;

    protected function setUp(): void
    {
        $this->esaboraSISHService = $this->createMock(EsaboraSISHService::class);
        $this->serializer = $this->createMock(SerializerInterface::class);
        $this->jobEventManager = $this->createMock(JobEventManager::class);
        $this->partnerRepository = $this->createMock(PartnerRepository::class);
    }

    public function testHandle()
    {
        $this->esaboraSISHService
            ->expects($this->atLeast(1))
            ->method('pushDossier')
            ->willReturn($this->getDossierSISHResponse('ws_dossier.json'));

        $handler = new DossierServiceHandler($this->esaboraSISHService);

        $handler->handle($this->getDossierMessageSISH());
    }

    public function testGetPriority(): void
    {
        $handler = new DossierServiceHandler($this->esaboraSISHService);
        $this->assertSame(2, $handler::getPriority());
    }
}
