<?php

namespace App\Tests\Unit\Service\Esabora;

use App\Service\Esabora\EsaboraSISHService;
use App\Service\UploadHandlerService;
use App\Tests\FileHelper;
use App\Tests\Unit\Messenger\DossierMessageTrait;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Component\HttpFoundation\Response;

class EsaboraSISHServiceTest extends KernelTestCase
{
    use DossierMessageTrait;
    use FileHelper;

    private UploadHandlerService $uploadHandlerService;
    private LoggerInterface $logger;
    private ?string $tempFilepath;

    protected function setUp(): void
    {
        $this->tempFilepath = $this->getTempFilepath();
        $this->uploadHandlerService = $this->createMock(UploadHandlerService::class);
        $this->logger = $this->createMock(LoggerInterface::class);
    }

    public function testPushDossierAdressToEsaboraSasSuccess(): void
    {
        $filepath = __DIR__.'/../../../../tools/wiremock/src/Resources/Esabora/sish/ws_dossier_adresse.json';
        $esaboraService = $this->getEsaboraSISHService($filepath);
        $response = $esaboraService->pushAdresse($this->getDossierMessageSISH());
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertEquals(3, $response->getSasId());
    }

    public function testPushDossierToEsaboraSasSuccess(): void
    {
        $filepath = __DIR__.'/../../../../tools/wiremock/src/Resources/Esabora/sish/ws_dossier.json';
        $esaboraService = $this->getEsaboraSISHService($filepath);
        $response = $esaboraService->pushDossier($this->getDossierMessageSISH());
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertEquals(14, $response->getSasId());
    }

    public function testPushDossierPersonneToEsaboraSasSuccess(): void
    {
        $filepath = __DIR__.'/../../../../tools/wiremock/src/Resources/Esabora/sish/ws_dossier_personne.json';
        $esaboraService = $this->getEsaboraSISHService($filepath);
        $response = $esaboraService->pushPersonne($this->getDossierMessageSISH(), $this->getDossierMessageSISHPersonne());
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertEquals(1, $response->getSasId());
    }

    private function getEsaboraSISHService(string $filepath): EsaboraSISHService
    {
        $mockResponse = new MockResponse(file_get_contents($filepath));
        $mockHttpClient = new MockHttpClient($mockResponse);

        return new EsaboraSISHService($mockHttpClient, $this->logger, $this->uploadHandlerService);
    }
}
