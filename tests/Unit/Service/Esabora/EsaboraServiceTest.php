<?php

namespace App\Tests\Unit\Service\Esabora;

use App\Entity\Affectation;
use App\Entity\Partner;
use App\Entity\Signalement;
use App\Manager\AffectationManager;
use App\Messenger\Message\DossierMessage;
use App\Service\Esabora\DossierResponse;
use App\Service\Esabora\EsaboraService;
use Faker\Factory;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

class EsaboraServiceTest extends KernelTestCase
{
    private AffectationManager $affectationManager;
    private LoggerInterface $logger;

    protected function setUp(): void
    {
        $this->affectationManager = $this->createMock(AffectationManager::class);
        $this->logger = $this->createMock(LoggerInterface::class);
    }

    public function testPushDossierToEsaboraSas(): void
    {
        $filepath = __DIR__.'/../../../../tools/wiremock/src/Resources/Esabora/ws_import.json';
        $mockResponse = new MockResponse(file_get_contents($filepath));

        $mockHttpClient = new MockHttpClient($mockResponse);
        $esaboraService = new EsaboraService($mockHttpClient, $this->affectationManager, $this->logger);
        $dossierMessage = $this->getDossierMessage();
        $response = $esaboraService->pushDossier($dossierMessage);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertStringContainsString('insert', $response->getContent());
    }

    public function testgetStateDossierFromEsaboraSas(): void
    {
        $filepath = __DIR__.'/../../../../tools/wiremock/src/Resources/Esabora/ws_etat_dossier_sas/etat_importe.json';
        $mockResponse = new MockResponse(file_get_contents($filepath));

        $mockHttpClient = new MockHttpClient($mockResponse);
        $esaboraService = new EsaboraService($mockHttpClient, $this->affectationManager, $this->logger);
        $dossierResponse = $esaboraService->getStateDossier($this->getAffectation());

        $this->assertInstanceOf(DossierResponse::class, $dossierResponse);
        $this->assertEquals('00000000-0000-0000-2022-000000000001', $dossierResponse->getSasReference());
        $this->assertEquals('Importé', $dossierResponse->getSasEtat());
        $this->assertEquals(200, $dossierResponse->getStatusCode());
        $this->assertEquals('en cours', $dossierResponse->getEtat());
    }

    private function getDossierMessage(): DossierMessage
    {
        $faker = Factory::create();

        return (new DossierMessage())
            ->setUrl($faker->url())
            ->setToken($faker->password(20))
            ->setPartnerId($faker->randomDigit())
            ->setSignalementId($faker->randomDigit())
            ->setReference($faker->uuid())
            ->setNomUsager($faker->lastName())
            ->setPrenomUsager($faker->firstName())
            ->setMailUsager($faker->email())
            ->setTelephoneUsager($faker->phoneNumber())
            ->setAdresseSignalement($faker->address())
            ->setCodepostaleSignalement($faker->postcode())
            ->setVilleSignalement($faker->city())
            ->setEtageSignalement('1')
            ->setNumeroAppartementSignalement('2')
            ->setNumeroAdresseSignalement('10')
            ->setLatitudeSignalement(0)
            ->setLongitudeSignalement(0)
            ->setDateOuverture('01/01/2022')
            ->setDossierCommentaire(null)
            ->setPiecesJointesObservation(null);
    }

    private function getAffectation(): Affectation
    {
        $faker = Factory::create();

        return (new Affectation())
            ->setPartner(
                (new Partner())
                    ->setEsaboraToken($faker->password(20))
                    ->setEsaboraUrl($faker->url())
            )->setSignalement(
                (new Signalement())
                    ->setUuid($faker->uuid())
            );
    }
}
