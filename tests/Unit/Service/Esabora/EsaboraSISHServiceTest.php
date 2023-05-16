<?php

namespace App\Tests\Unit\Service\Esabora;

use App\Entity\Enum\PartnerType;
use App\Service\Esabora\EsaboraSISHService;
use App\Service\UploadHandlerService;
use App\Tests\FileHelper;
use App\Tests\FixturesHelper;
use App\Tests\Unit\Messenger\DossierMessageTrait;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Component\HttpFoundation\Response;

class EsaboraSISHServiceTest extends KernelTestCase
{
    use DossierMessageTrait;
    use FileHelper;
    use FixturesHelper;

    public const PATH_RESOURCE_JSON = '/../../../../tools/wiremock/src/Resources/Esabora/sish/';

    private MockObject|UploadHandlerService $uploadHandlerService;
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
        $filepath = __DIR__.self::PATH_RESOURCE_JSON.'ws_dossier_adresse.json';
        $esaboraService = $this->getEsaboraSISHService($filepath);
        $response = $esaboraService->pushAdresse($this->getDossierMessageSISH());
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertEquals(3, $response->getSasId());
    }

    public function testPushDossierToEsaboraSasSuccess(): void
    {
        $filepath = __DIR__.self::PATH_RESOURCE_JSON.'ws_dossier.json';
        $esaboraService = $this->getEsaboraSISHService($filepath);
        $response = $esaboraService->pushDossier($this->getDossierMessageSISH());
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertEquals(14, $response->getSasId());
    }

    public function testPushDossierPersonneToEsaboraSasSuccess(): void
    {
        $filepath = __DIR__.self::PATH_RESOURCE_JSON.'ws_dossier_personne.json';
        $esaboraService = $this->getEsaboraSISHService($filepath);
        $response = $esaboraService->pushPersonne(
            $this->getDossierMessageSISH(),
            $this->getDossierMessageSISHPersonne()
        );
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertEquals(1, $response->getSasId());
    }

    public function testGetStateDossierFromEsaboraSas(): void
    {
        $filepath = __DIR__.self::PATH_RESOURCE_JSON.'ws_etat_dossier_sas/etat_importe.json';
        $mockResponse = new MockResponse(file_get_contents($filepath));

        $mockHttpClient = new MockHttpClient($mockResponse);
        $esaboraService = new EsaboraSISHService($mockHttpClient, $this->logger, $this->uploadHandlerService);

        $dossierStateSISHResponse = $esaboraService->getStateDossier($this->getAffectation(PartnerType::ARS));

        $this->assertEquals(200, $dossierStateSISHResponse->getStatusCode());
        $this->assertNull($dossierStateSISHResponse->getErrorReason());
    }

    public function testGetVisitesDossierFromEsaboraSas(): void
    {
        $filepath = __DIR__.self::PATH_RESOURCE_JSON.'ws_visites_dossier_sas.json';
        $mockResponse = new MockResponse(file_get_contents($filepath));

        $mockHttpClient = new MockHttpClient($mockResponse);
        $esaboraService = new EsaboraSISHService($mockHttpClient, $this->logger, $this->uploadHandlerService);
        $dossierVisiteSISHCollectionResponse = $esaboraService->getVisiteDossier($this->getAffectation(PartnerType::ARS));

        $this->assertEquals(200, $dossierVisiteSISHCollectionResponse->getStatusCode());
        $this->assertNull($dossierVisiteSISHCollectionResponse->getErrorReason());
        $this->assertCount(2, $dossierVisiteSISHCollectionResponse->getCollection());
    }

    public function testGetArretesDossierFromEsaboraSas(): void
    {
        $filepath = __DIR__.self::PATH_RESOURCE_JSON.'ws_arretes_dossier_sas.json';
        $mockResponse = new MockResponse(file_get_contents($filepath));

        $mockHttpClient = new MockHttpClient($mockResponse);
        $esaboraService = new EsaboraSISHService($mockHttpClient, $this->logger, $this->uploadHandlerService);
        $dossierArreteSISHCollectionResponse = $esaboraService->getArreteDossier($this->getAffectation(PartnerType::ARS));

        $this->assertEquals(200, $dossierArreteSISHCollectionResponse->getStatusCode());
        $this->assertNull($dossierArreteSISHCollectionResponse->getErrorReason());
        $this->assertCount(1, $dossierArreteSISHCollectionResponse->getCollection());
    }

    private function getEsaboraSISHService(string $filepath): EsaboraSISHService
    {
        $mockResponse = new MockResponse(file_get_contents($filepath));
        $mockHttpClient = new MockHttpClient($mockResponse);
        $this->uploadHandlerService
            ->expects($this->atLeast(str_contains('ws_dossier', $filepath) ? 1 : 0))
            ->method('getTmpFilepath')
            ->willReturn($this->tempFilepath);

        return new EsaboraSISHService($mockHttpClient, $this->logger, $this->uploadHandlerService);
    }
}
