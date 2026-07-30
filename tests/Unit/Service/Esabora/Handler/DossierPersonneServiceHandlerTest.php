<?php

namespace App\Tests\Unit\Service\Esabora\Handler;

use App\Service\Interconnection\Esabora\EsaboraSISHService;
use App\Service\Interconnection\Esabora\Handler\DossierPersonneServiceHandler;
use App\Tests\FixturesHelper;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;

class DossierPersonneServiceHandlerTest extends TestCase
{
    use FixturesHelper;

    protected MockObject&EsaboraSISHService $esaboraSISHService;

    protected function setUp(): void
    {
        $this->esaboraSISHService = $this->createMock(EsaboraSISHService::class);
    }

    public function testHandle(): void
    {
        $this->esaboraSISHService
            ->expects($this->atLeast(1))
            ->method('pushPersonne')
            ->willReturn($this->getDossierSISHResponse('ws_dossier_personne.json'));

        $handler = new DossierPersonneServiceHandler($this->esaboraSISHService);

        $dossierMessageSISH = $this->getDossierMessageSISH();
        $handler->handle($dossierMessageSISH);
    }

    public function testPriority(): void
    {
        $attributes = (new \ReflectionClass(DossierPersonneServiceHandler::class))->getAttributes(AsTaggedItem::class);

        $this->assertCount(1, $attributes);
        $this->assertSame(1, $attributes[0]->newInstance()->priority);
    }
}
