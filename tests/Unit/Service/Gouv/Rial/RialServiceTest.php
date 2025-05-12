<?php

namespace App\Tests\Unit\Service\Gouv\Rial;

use App\Service\Gouv\Rial\RialService;
use App\Service\Gouv\Rial\Response\InvariantsFiscaux;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

class RialServiceTest extends TestCase
{
    private const BAN_ID = '30348_0430_00015';
    private const INVARIANT_1 = '920020145586';
    private RialService $rialService;

    protected function setUp(): void
    {
        $mockResponse = new MockResponse(
            file_get_contents(__DIR__.'/../../../../files/betagouv/get_api_rial_invariants_response.json')
        );
        $mockHttpClient = new MockHttpClient($mockResponse);
        $this->rialService = new RialService($mockHttpClient, $this->createMock(LoggerInterface::class));
    }

    public function testGetInvariants(): void
    {
        /** @var InvariantsFiscaux $listeInvariants */
        $listeInvariants = $this->rialService->getInvariantsFiscaux(self::BAN_ID);
        $this->assertIsArray($listeInvariants->getInvariantsFiscaux());
        $this->assertCount(2, $listeInvariants->getInvariantsFiscaux());
        $this->assertSame(self::INVARIANT_1, $listeInvariants->getFirstInvariantFiscal());
    }
}
