<?php

namespace App\Tests\Unit\Service\Esabora\Response;

use App\Service\Esabora\Response\DossierPushSISHResponse;
use PHPUnit\Framework\TestCase;

class DossierPushSISHResponseTest extends TestCase
{
    /**
     * @dataProvider provideFilename
     */
    public function testDossierResponseSuccessfullyCreated(string $filename): void
    {
        $filepath = __DIR__.'/../../../../../tools/wiremock/src/Resources/Esabora/sish/'.$filename;
        $responseEsabora = json_decode(file_get_contents($filepath), true);

        $dossierResponse = new DossierPushSISHResponse($responseEsabora, 200);
        $this->assertNotNull($dossierResponse->getSasId());
        $this->assertEquals(200, $dossierResponse->getStatusCode());
        $this->assertNull($dossierResponse->getErrorReason());
    }

    public function provideFilename(): \Generator
    {
        yield 'Response Dossier Adresse' => ['ws_dossier_adresse.json'];
        yield 'Response Dossier' => ['ws_dossier.json'];
        yield 'Response Dossier Personne' => ['ws_dossier_personne.json'];
    }
}
