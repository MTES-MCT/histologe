<?php

namespace App\Tests\Unit\Service\Esabora;

use App\Service\Esabora\Response\DossierStateSCHSResponse;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;

class DossierResponseTest extends TestCase
{
    public function testDossierResponseSuccessfullyCreated(): void
    {
        $filepath = __DIR__.'/../../../../tools/wiremock/src/Resources/Esabora/schs/ws_etat_dossier_sas/etat_importe.json';
        $responseEsabora = json_decode(file_get_contents($filepath), true);

        $dossierResponse = new DossierStateSCHSResponse($responseEsabora, 200);
        $this->assertEquals('00000000-0000-0000-2022-000000000001', $dossierResponse->getSasReference());
        $this->assertEquals('Importé', $dossierResponse->getSasEtat());
        $this->assertEquals('20221', $dossierResponse->getId());
        $this->assertEquals('2022-1', $dossierResponse->getNumero());
        $this->assertEquals('Traité', $dossierResponse->getStatutAbrege());
        $this->assertEquals('Traité', $dossierResponse->getStatut());
        $this->assertEquals('en cours', $dossierResponse->getEtat());
        $this->assertNull($dossierResponse->getDateCloture());
        $this->assertEquals(200, $dossierResponse->getStatusCode());
        $this->assertNull($dossierResponse->getErrorReason());
    }

    public function testDossierResponseFailed(): void
    {
        $responseEsabora = ['message' => 'Lorem ipsum', 'statusCode' => Response::HTTP_BAD_REQUEST];

        $dossierResponse = new DossierStateSCHSResponse($responseEsabora, Response::HTTP_BAD_REQUEST);
        $this->assertNull($dossierResponse->getSasReference());
        $this->assertNull($dossierResponse->getSasEtat());
        $this->assertNull($dossierResponse->getId());
        $this->assertNull($dossierResponse->getNumero());
        $this->assertNull($dossierResponse->getStatutAbrege());
        $this->assertNull($dossierResponse->getStatut());
        $this->assertNull($dossierResponse->getEtat());
        $this->assertNull($dossierResponse->getDateCloture());
        $this->assertEquals(Response::HTTP_BAD_REQUEST, $dossierResponse->getStatusCode());
        $this->assertNotNull($dossierResponse->getErrorReason());
    }
}
