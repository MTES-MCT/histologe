<?php

namespace App\Tests\Unit\Service\Gouv\Rnb;

use App\Service\Gouv\Rnb\RnbService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

class RnbServiceTest extends TestCase
{
    private const BAN_ID = '30348_0430_00015';
    private const RNB_ID = 'FQYN6F6WPEJ8';
    private RnbService $rnbService;

    protected function setUp(): void
    {
        $mockResponse = new MockResponse(
            (string) file_get_contents(__DIR__.'/../../../../files/betagouv/get_api_rnb_buildings_response.json')
        );
        $mockHttpClient = new MockHttpClient($mockResponse);
        /** @var MockObject&LoggerInterface $logger */
        $logger = $this->createMock(LoggerInterface::class);
        $this->rnbService = new RnbService($mockHttpClient, $logger);
    }

    public function testGetBuildings(): void
    {
        $buildings = $this->rnbService->getBuildings(self::BAN_ID);
        $this->assertIsArray($buildings);
        $this->assertCount(1, $buildings);
        $this->assertSame(self::RNB_ID, $buildings[0]->getRnbId());
    }
}
