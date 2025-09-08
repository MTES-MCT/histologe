<?php

namespace App\Tests\Unit\Service\Gouv\Rial;

use App\Service\Gouv\Rial\RialService;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

class RialServiceTest extends TestCase
{
    public const string API_WIREMOCK_URL = 'http://localhost:8082';

    public function testGetAccessToken(): void
    {
        $rialService = $this->getRialService(__DIR__.'/../../../../../tools/wiremock/src/Resources/Rial/token.json');
        $response = $rialService->getAccessToken();
        $this->assertEquals('fake-access-token', $response);
    }

    public function testGetLocaux(): void
    {
        $rialService = $this->getRialService(__DIR__.'/../../../../../tools/wiremock/src/Resources/Rial/list.json');
        $rialService->setAccessToken('fake-access-token');
        $response = $rialService->searchLocauxByBanId('2a004_0820_00002');
        $this->assertIsArray($response);
    }

    public function testGetNullLocaux(): void
    {
        $rialService = $this->getRialService(__DIR__.'/../../../../../tools/wiremock/src/Resources/Rial/list.json');
        $rialService->setAccessToken('fake-access-token');
        $response = $rialService->searchLocauxByBanId('Jolie patate');
        $this->assertNull($response);
    }

    public function testGetSingleLocal(): void
    {
        $rialService = $this->getRialService(__DIR__.'/../../../../../tools/wiremock/src/Resources/Rial/infos.json');
        $rialService->setAccessToken('fake-access-token');
        $response = $rialService->searchLocalByIdFiscal('2A0049934XXX');
        $this->assertIsArray($response);
        $this->assertArrayHasKey('descriptifGeneralLocal', $response);
    }

    private function getRialService(string $file): RialService
    {
        /** @var string $responseFile */
        $responseFile = file_get_contents($file);
        /** @var LoggerInterface $logger */
        $logger = $this->createMock(LoggerInterface::class);
        $mockResponse = new MockResponse($responseFile);
        $mockHttpClient = new MockHttpClient($mockResponse);

        return new RialService(
            $mockHttpClient,
            $logger,
            self::API_WIREMOCK_URL,
            'rialKey',
            'rialSecret',
        );
    }
}
