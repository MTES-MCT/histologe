<?php

namespace App\Tests\Unit\Service\Gouv\Insee;

use App\Service\Gouv\Insee\SireneService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

class SireneServiceTest extends TestCase
{
    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws ClientExceptionInterface
     */
    public function testGetSiretSuccessful(): void
    {
        $jsonContent = (string) file_get_contents(__DIR__.'/../../../../../tools/wiremock/src/Resources/Sirene/siret.json');

        $mockResponse = new MockResponse($jsonContent, [
            'http_code' => 200,
            'response_headers' => ['Content-Type' => 'application/json'],
        ]);

        $mockHttpClient = new MockHttpClient(function ($method, $url, $options) use ($mockResponse) {
            $this->assertSame('GET', $method);
            $this->assertSame('https://api.insee.fr/api-sirene/3.11/siret/21130055306112', $url);
            $this->assertContains('X-INSEE-Api-Key-Integration: fake-api-key', $options['headers']);

            return $mockResponse;
        });

        $sireneService = new SireneService($mockHttpClient, 'fake-api-key');
        $siretInformation = $sireneService->getSiret('21130055306112');

        $this->assertSame('21130055306112', $siretInformation->siret);
        $this->assertSame('13203', $siretInformation->codeCommune);
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws ClientExceptionInterface
     */
    public function testGetSiretThrowsUnexpectedValueExceptionWhenCodeCommuneMissing(): void
    {
        $mockResponse = new MockResponse((string) json_encode([
            'header' => ['statut' => 404, 'message' => 'Aucun établissement trouvé pour le SIRET "12345678912345"'],
        ]));

        $mockHttpClient = new MockHttpClient($mockResponse);
        $sireneService = new SireneService($mockHttpClient, 'fake-api-key');

        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('Aucun code commune trouvé pour le SIRET "12345678912345".');

        $sireneService->getSiret('12345678912345');
    }
}
