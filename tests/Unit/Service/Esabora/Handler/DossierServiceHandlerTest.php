<?php

namespace App\Tests\Unit\Service\Esabora\Handler;

use App\Manager\JobEventManager;
use App\Repository\PartnerRepository;
use App\Service\Esabora\EsaboraSISHService;
use App\Service\Esabora\Handler\DossierServiceHandler;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\SerializerInterface;

class DossierServiceHandlerTest extends TestCase
{
    protected EsaboraSISHService $esaboraSISHService;
    protected SerializerInterface $serializer;
    protected JobEventManager $jobEventManager;
    protected PartnerRepository $partnerRepository;

    protected function setUp(): void
    {
        $this->esaboraSISHService = $this->createMock(EsaboraSISHService::class);
        $this->serializer = $this->createMock(SerializerInterface::class);
        $this->jobEventManager = $this->createMock(JobEventManager::class);
        $this->partnerRepository = $this->createMock(PartnerRepository::class);
    }

    public function testGetPriority(): void
    {
        $handler = new DossierServiceHandler(
            $this->esaboraSISHService,
            $this->serializer,
            $this->jobEventManager,
            $this->partnerRepository
        );

        $this->assertSame(2, $handler::getPriority());
    }
}
