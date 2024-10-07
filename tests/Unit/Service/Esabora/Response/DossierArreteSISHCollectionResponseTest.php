<?php

namespace App\Tests\Unit\Service\Esabora\Response;

use App\Service\Interconnection\Esabora\Response\DossierArreteSISHCollectionResponse;
use PHPUnit\Framework\TestCase;

class DossierArreteSISHCollectionResponseTest extends TestCase
{
    public function testDossierArreteSISHCollectionResponseSuccessfullyCreated(): void
    {
        $filepath = __DIR__.'/../../../../../tools/wiremock/src/Resources/Esabora/sish/ws_arretes_dossier_sas.json';
        $responseEsabora = json_decode(file_get_contents($filepath), true);

        $dossierArreteSISHCollectionResponse = new DossierArreteSISHCollectionResponse($responseEsabora, 200);
        $dossiersArreteSISH = $dossierArreteSISHCollectionResponse->getCollection();
        $this->assertCount(1, $dossiersArreteSISH);
        $this->assertEquals('Histologe', $dossiersArreteSISH[0]->getLogicielProvenance());
        $this->assertEquals('00000000-0000-0000-2023-000000000010', $dossiersArreteSISH[0]->getReferenceDossier());
        $this->assertEquals('2023/DD13/0010', $dossiersArreteSISH[0]->getDossNum());
        $this->assertEquals('14/06/2023', $dossiersArreteSISH[0]->getArreteDate());
        $this->assertEquals('2023/DD13/00664', $dossiersArreteSISH[0]->getArreteNumero());
        $this->assertEquals('Arrêté L.511-11 - Suroccupation', $dossiersArreteSISH[0]->getArreteType());

        $this->assertEquals(200, $dossierArreteSISHCollectionResponse->getStatusCode());
        $this->assertNull($dossierArreteSISHCollectionResponse->getErrorReason());
    }
}
