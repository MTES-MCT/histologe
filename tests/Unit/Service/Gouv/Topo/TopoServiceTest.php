<?php

namespace App\Tests\Unit\Service\Gouv\Topo;

use App\Service\Gouv\Topo\TopoService;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

class TopoServiceTest extends TestCase
{
    public function testSearchVoiesSuccess(): void
    {
        $mockData = [
            'results' => [
                [
                    'code_dep' => '63',
                    'code_commune' => '214',
                    'code_voie' => '0136',
                    'nature_de_voie' => 'RUE',
                    'libelle' => 'DE LOUBRETTE',
                ],
                [
                    'code_dep' => '63',
                    'code_commune' => '214',
                    'code_voie' => 'B060',
                    'nature_de_voie' => '',
                    'libelle' => 'LOUBRETTE',
                ],
            ],
        ];

        $mockResponse = new MockResponse(json_encode($mockData), [
            'http_code' => 200,
            'response_headers' => ['Content-Type' => 'application/json'],
        ]);

        $httpClient = new MockHttpClient($mockResponse);
        $logger = $this->createMock(LoggerInterface::class);
        $topoService = new TopoService($httpClient, $logger);

        $results = $topoService->searchVoies('63', '214', 'LOUBRETTE');

        $this->assertCount(2, $results);
        $this->assertEquals('0136', $results[0]['code_voie']);
        $this->assertEquals('DE LOUBRETTE', $results[0]['libelle']);
        
        $this->assertEquals('GET', $mockResponse->getRequestMethod());
        $url = $mockResponse->getRequestUrl();
        $this->assertStringContainsString('code_dep%3D%2263%22', $url);
        $this->assertStringContainsString('code_commune%3D%22214%22', $url);
        $this->assertStringContainsString('search(libelle%2C%22LOUBRETTE%22)', $url);
    }

    public function testSearchVoiesError(): void
    {
        $mockResponse = new MockResponse('Internal Server Error', [
            'http_code' => 500,
        ]);

        $httpClient = new MockHttpClient($mockResponse);
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())->method('error');
        
        $topoService = new TopoService($httpClient, $logger);

        $results = $topoService->searchVoies('63', '214', 'LOUBRETTE');

        $this->assertEmpty($results);
    }
}
