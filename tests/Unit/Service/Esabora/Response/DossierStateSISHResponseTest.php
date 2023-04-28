<?php

namespace App\Tests\Unit\Service\Esabora\Response;

use App\Service\Esabora\Enum\EsaboraStatus;
use App\Service\Esabora\Response\DossierStateSISHResponse;
use PHPUnit\Framework\TestCase;

class DossierStateSISHResponseTest extends TestCase
{
    public function testDossierResponseSuccessfullyCreated(): void
    {
        $filepath = __DIR__.'/../../../../../tools/wiremock/src/Resources/Esabora/sish/ws_etat_dossier_sas/etat_importe.json';
        $responseEsabora = json_decode(file_get_contents($filepath), true);

        $dossierResponse = new DossierStateSISHResponse($responseEsabora, 200);
        $this->assertEquals('00000000-0000-0000-2022-000000000008', $dossierResponse->getReferenceDossier());
        $this->assertEquals('Importé', $dossierResponse->getSasEtat());
        $this->assertEquals('14/04/2023 10:16', $dossierResponse->getSasDateDecision());
        $this->assertNull($dossierResponse->getSasCauseRefus());
        $this->assertEquals('2023', $dossierResponse->getDossId());
        $this->assertEquals('2023/SISH/0001', $dossierResponse->getDossNum());
        $this->assertEquals('45 Boulevard Auguste Blanquis - PARIS', $dossierResponse->getDossObjet());
        $this->assertNull($dossierResponse->getDossDateCloture());
        $this->assertEquals(EsaboraStatus::ESABORA_IN_PROGRESS->value, $dossierResponse->getEtat());
        $this->assertEquals('INS', $dossierResponse->getDossTypeCode());
        $this->assertEquals('Insalubrité', $dossierResponse->getDossTypeLib());
        $this->assertEquals('A traiter', $dossierResponse->getDossStatut());
        $this->assertEquals('A traiter', $dossierResponse->getDossStatutAbr());
        $this->assertEquals(200, $dossierResponse->getStatusCode());
        $this->assertNull($dossierResponse->getErrorReason());
    }
}
