<?php

namespace App\Tests\Unit\Service\Esabora\Handler;

use App\Service\Interconnection\Esabora\EsaboraSISHService;
use App\Service\Interconnection\Esabora\Handler\DossierAdresseServiceHandler;
use App\Tests\FixturesHelper;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;

class DossierAdresseServiceHandlerTest extends TestCase
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
            ->method('pushAdresse')
            ->willReturn($this->getDossierSISHResponse('ws_dossier_adresse.json'));

        $handler = new DossierAdresseServiceHandler(
            $this->esaboraSISHService,
        );

        $dossierMessageSISH = $this->getDossierMessageSISH();
        $handler->handle($dossierMessageSISH);
    }

    public function testPriority(): void
    {
        $attributes = (new \ReflectionClass(DossierAdresseServiceHandler::class))->getAttributes(AsTaggedItem::class);

        $this->assertCount(1, $attributes);
        $this->assertSame(3, $attributes[0]->newInstance()->priority);
    }
}
