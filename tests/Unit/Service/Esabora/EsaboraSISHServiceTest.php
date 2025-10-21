<?php

namespace App\Tests\Unit\Service\Esabora;

use App\Entity\Enum\PartnerType;
use App\Service\Interconnection\Esabora\AbstractEsaboraService;
use App\Service\Interconnection\Esabora\EsaboraSISHService;
use App\Service\UploadHandlerService;
use App\Tests\FileHelper;
use App\Tests\FixturesHelper;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Component\HttpFoundation\Response;

class EsaboraSISHServiceTest extends KernelTestCase
{
    use FileHelper;
    use FixturesHelper;

    public const PATH_RESOURCE_JSON = '/../../../../tools/wiremock/src/Resources/Esabora/sish/';

    private MockObject|UploadHandlerService $uploadHandlerService;
    private MockObject|LoggerInterface $logger;
    private ?string $tempFilepath;

    protected function setUp(): void
    {
        $this->tempFilepath = $this->getTempFilepath();
        $this->uploadHandlerService = $this->createMock(UploadHandlerService::class);
        $this->logger = $this->createMock(LoggerInterface::class);
    }

    public function testPushDossierAddressToEsaboraSasSuccess(): void
    {
        $filepath = __DIR__.self::PATH_RESOURCE_JSON.'ws_dossier_adresse.json';
        $esaboraService = $this->getEsaboraSISHService($filepath);
        $response = $esaboraService->pushAdresse($this->getDossierMessageSISH(
            AbstractEsaboraService::ACTION_PUSH_DOSSIER_ADRESSE
        ));
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertEquals(3, $response->getSasId());
    }

    public function testPushDossierToEsaboraSasSuccess(): void
    {
        $filepath = __DIR__.self::PATH_RESOURCE_JSON.'ws_dossier.json';
        $esaboraService = $this->getEsaboraSISHService($filepath);
        $response = $esaboraService->pushDossier($this->getDossierMessageSISH(AbstractEsaboraService::ACTION_PUSH_DOSSIER));
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertEquals(14, $response->getSasId());
    }

    public function testPushDossierPersonneToEsaboraSasSuccess(): void
    {
        $filepath = __DIR__.self::PATH_RESOURCE_JSON.'ws_dossier_personne.json';
        $esaboraService = $this->getEsaboraSISHService($filepath);
        $response = $esaboraService->pushPersonne(
            $this->getDossierMessageSISH(AbstractEsaboraService::ACTION_PUSH_DOSSIER_PERSONNE),
            $this->getDossierMessageSISHPersonneOccupant()
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
        $affectation = $this->getAffectation(PartnerType::ARS);
        $dossierStateSISHResponse = $esaboraService->getStateDossier($affectation, $affectation->getSignalement()->getUuid());

        $this->assertEquals(200, $dossierStateSISHResponse->getStatusCode());
        $this->assertNull($dossierStateSISHResponse->getErrorReason());
    }

    public function testGetVisitesDossierFromEsaboraSas(): void
    {
        $filepath = __DIR__.self::PATH_RESOURCE_JSON.'ws_visites_dossier_sas.json';
        $mockResponse = new MockResponse(file_get_contents($filepath));

        $mockHttpClient = new MockHttpClient($mockResponse);
        $esaboraService = new EsaboraSISHService($mockHttpClient, $this->logger, $this->uploadHandlerService);
        $affectation = $this->getAffectation(PartnerType::ARS);
        $dossierVisiteSISHCollectionResponse = $esaboraService->getVisiteDossier($affectation, $affectation->getSignalement()->getUuid());

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
        $affectation = $this->getAffectation(PartnerType::ARS);
        $dossierArreteSISHCollectionResponse = $esaboraService->getArreteDossier($affectation, $affectation->getSignalement()->getUuid());

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
