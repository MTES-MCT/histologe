<?php

namespace App\Tests\Unit\Service\Gouv\Rnb;

use App\Service\Gouv\Rial\RialService;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

class RialServiceTest extends TestCase
{
    public const string API_WIREMOCK_URL = 'http://localhost:8082';

    private RialService $rialService;

    protected function setUp(): void
    {
        /** @var LoggerInterface $logger */
        $logger = $this->createMock(LoggerInterface::class);
        $mockResponse = new MockResponse('{"listeIdentifiantsFiscaux": ["2A0049934148"]}');
        $mockHttpClient = new MockHttpClient($mockResponse);
        $this->rialService = new RialService(
            $mockHttpClient,
            $logger,
            self::API_WIREMOCK_URL,
            'rialKey',
            'rialSecret',
        );
    }

    public function testGetNullAccessToken(): void
    {
        $response = $this->rialService->getAccesssToken();
        $this->assertNull($response);
    }

    public function testGetNullLocaux(): void
    {
        $response = $this->rialService->searchLocauxByAdresse('2a004_0820_00002');
        $this->assertNull($response);
    }
}
