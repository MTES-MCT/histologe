<?php

namespace App\Tests\Unit\Service\Esabora\Handler;

use App\Service\Interconnection\Esabora\EsaboraSISHService;
use App\Service\Interconnection\Esabora\Handler\DossierPersonneServiceHandler;
use App\Tests\FixturesHelper;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;

class DossierPersonneServiceHandlerTest extends TestCase
{
    use FixturesHelper;

    public function testHandle(): void
    {
        $esaboraSISHService = $this->createMock(EsaboraSISHService::class);
        $esaboraSISHService
            ->expects($this->atLeast(1))
            ->method('pushPersonne')
            ->willReturn($this->getDossierSISHResponse('ws_dossier_personne.json'));

        $handler = new DossierPersonneServiceHandler($esaboraSISHService);

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
