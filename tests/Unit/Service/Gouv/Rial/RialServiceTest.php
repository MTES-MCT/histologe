<?php

namespace App\Tests\Unit\Service\Gouv\Rial;

use App\Service\Gouv\Rial\RialService;
use PHPUnit\Framework\MockObject\MockObject;
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

    public function testGetAccessTokenReuseValid(): void
    {
        $rialService = $this->getRialService(__DIR__.'/../../../../../tools/wiremock/src/Resources/Rial/token.json');
        $rialService->setAccessToken('existing-token', 3600);
        $response = $rialService->getAccessToken();
        $this->assertEquals('existing-token', $response);
    }

    public function testGetAccessTokenRenewExpired(): void
    {
        $rialService = $this->getRialService(__DIR__.'/../../../../../tools/wiremock/src/Resources/Rial/token.json');
        $rialService->setAccessToken('expired-token', -10);
        $response = $rialService->getAccessToken();
        $this->assertEquals('fake-access-token', $response);
    }

    public function testGetAccessTokenRenewNearExpiration(): void
    {
        $rialService = $this->getRialService(__DIR__.'/../../../../../tools/wiremock/src/Resources/Rial/token.json');
        $rialService->setAccessToken('almost-expired-token', 50); // < 60s
        $response = $rialService->getAccessToken();
        $this->assertEquals('fake-access-token', $response);
    }

    public function testGetAccessTokenError(): void
    {
        /** @var LoggerInterface&MockObject $logger */
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())->method('warning');

        $mockResponse = new MockResponse('', ['http_code' => 500]);
        $mockHttpClient = new MockHttpClient($mockResponse);

        $rialService = new RialService(
            $mockHttpClient,
            $logger,
            self::API_WIREMOCK_URL,
            'rialKey',
            'rialSecret',
            '1'
        );

        $response = $rialService->getAccessToken();
        $this->assertNull($response);
    }

    public function testNoResetInterface(): void
    {
        $reflection = new \ReflectionClass(RialService::class);
        $this->assertNotContains('Symfony\Contracts\Service\ResetInterface', $reflection->getInterfaceNames());
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
        $responseFile = (string) file_get_contents($file);
        /** @var LoggerInterface&MockObject $logger */
        $logger = $this->createMock(LoggerInterface::class);
        $mockResponse = new MockResponse($responseFile);
        $mockHttpClient = new MockHttpClient($mockResponse);

        return new RialService(
            $mockHttpClient,
            $logger,
            self::API_WIREMOCK_URL,
            'rialKey',
            'rialSecret',
            '1'
        );
    }
}
