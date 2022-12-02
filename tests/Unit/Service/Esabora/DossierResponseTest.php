<?php

namespace App\Tests\Unit\Service\Esabora;

use App\Service\Esabora\DossierResponse;
use PHPUnit\Framework\TestCase;

class DossierResponseTest extends TestCase
{
    public function testDossierResponseSuccessfullyCreated(): void
    {
        $filepath = __DIR__.'/../../../../tools/wiremock/src/Resources/Esabora/ws_etat_dossier_sas/etat_non_importe.json';
        $responseEsabora = json_decode(file_get_contents($filepath), true);

        $dossierResponse = new DossierResponse($responseEsabora, 200);
        $this->assertEquals('00000000-0000-0000-2022-000000000002', $dossierResponse->getSasReference());
        $this->assertEquals('Non importé', $dossierResponse->getSasEtat());
        $this->assertEquals('20222', $dossierResponse->getId());
        $this->assertEquals('2022-2', $dossierResponse->getNumero());
        $this->assertNull($dossierResponse->getStatutAbrege());
        $this->assertNull($dossierResponse->getStatut());
        $this->assertEquals('en cours', $dossierResponse->getEtat());
        $this->assertNull($dossierResponse->getDateCloture());
        $this->assertEquals(200, $dossierResponse->getStatusCode());
    }
}
