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

        $jsonMockData = json_encode($mockData);
        $this->assertNotFalse($jsonMockData);

        $mockResponse = new MockResponse($jsonMockData, [
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

    public function testSearchVoiesEscapesLibelle(): void
    {
        $mockResponse = new MockResponse('{"results":[]}', [
            'http_code' => 200,
            'response_headers' => ['Content-Type' => 'application/json'],
        ]);

        $topoService = new TopoService(new MockHttpClient($mockResponse), $this->createStub(LoggerInterface::class));
        $topoService->searchVoies('63', '214', 'A" OR code_dep<>"0\\');

        parse_str((string) parse_url($mockResponse->getRequestUrl(), \PHP_URL_QUERY), $query);
        $this->assertSame(
            'code_dep="63" AND code_commune="214" AND search(libelle,"A\" OR code_dep<>\"0\\\\")',
            $query['where']
        );
    }

    public function testSearchVoiesRejectsInvalidCodes(): void
    {
        $httpClient = new MockHttpClient(function () {
            $this->fail('The API must not be called with invalid codes.');
        });
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->exactly(2))->method('warning');

        $topoService = new TopoService($httpClient, $logger);

        $this->assertSame([], $topoService->searchVoies('63" OR 1=1 --', '214', 'LOUBRETTE'));
        $this->assertSame([], $topoService->searchVoies('63', '2"4', 'LOUBRETTE'));
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
