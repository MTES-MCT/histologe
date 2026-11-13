<?php

namespace App\Tests\Unit\Service\Esabora\Handler;

use App\Service\Interconnection\Esabora\EsaboraSISHService;
use App\Service\Interconnection\Esabora\Handler\DossierServiceHandler;
use App\Tests\FixturesHelper;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;

class DossierServiceHandlerTest extends TestCase
{
    use FixturesHelper;

    public function testHandle(): void
    {
        $esaboraSISHService = $this->createMock(EsaboraSISHService::class);
        $esaboraSISHService
            ->expects($this->atLeast(1))
            ->method('pushDossier')
            ->willReturn($this->getDossierSISHResponse('ws_dossier.json'));

        $handler = new DossierServiceHandler($esaboraSISHService);

        $handler->handle($this->getDossierMessageSISH());
    }

    public function testPriority(): void
    {
        $attributes = (new \ReflectionClass(DossierServiceHandler::class))->getAttributes(AsTaggedItem::class);

        $this->assertCount(1, $attributes);
        $this->assertSame(2, $attributes[0]->newInstance()->priority);
    }
}
