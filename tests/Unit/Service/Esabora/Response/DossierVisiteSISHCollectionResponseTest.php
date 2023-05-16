<?php

namespace App\Tests\Unit\Service\Esabora\Response;

use App\Service\Esabora\Response\DossierVisiteSISHCollectionResponse;
use PHPUnit\Framework\TestCase;

class DossierVisiteSISHCollectionResponseTest extends TestCase
{
    public function testDossierVisiteSISHCollectionResponseSuccessfullyCreated(): void
    {
        $filepath = __DIR__.'/../../../../../tools/wiremock/src/Resources/Esabora/sish/ws_visites_dossier_sas.json';
        $responseEsabora = json_decode(file_get_contents($filepath), true);

        $dossierVisiteCollectionResponse = new DossierVisiteSISHCollectionResponse($responseEsabora, 200);
        $dossiersVisiteSISH = $dossierVisiteCollectionResponse->getCollection();
        $this->assertCount(2, $dossiersVisiteSISH);

        foreach ($dossiersVisiteSISH as $dossierVisiteSISH) {
            $this->assertEquals('Histologe', $dossierVisiteSISH->getSasLogicielProvenance());
            $this->assertEquals('00000000-0000-0000-2023-000000000010', $dossierVisiteSISH->getReferenceDossier());
            $this->assertEquals('2023/DD13/0010', $dossierVisiteSISH->getDossNum());
            $this->assertInstanceOf(
                \DateTimeImmutable::class,
                \DateTimeImmutable::createFromFormat('d/m/Y', $dossierVisiteSISH->getVisiteDateEnreg())
            );
            $this->assertInstanceOf(
                \DateTimeImmutable::class,
                \DateTimeImmutable::createFromFormat('d/m/Y H:i', $dossierVisiteSISH->getVisiteDate())
            );
            $this->assertTrue(
                'Tout est OK' === $dossierVisiteSISH->getVisiteObservations()
                || null === $dossierVisiteSISH->getVisiteObservations()
            );
            $this->assertNotNull($dossierVisiteSISH->getVisiteNum());
            $this->assertStringContainsString('Visite', $dossierVisiteSISH->getVisiteType());
            $this->assertStringContainsString('Effectuée', $dossierVisiteSISH->getVisiteStatut());
            $this->assertEquals('Terminé', $dossierVisiteSISH->getVisiteEtat());
            $this->assertTrue(\in_array($dossierVisiteSISH->getVisitePar(), ['ARS', 'SCHS', 'SH', 'STH']));
        }

        $this->assertEquals(200, $dossierVisiteCollectionResponse->getStatusCode());
        $this->assertNull($dossierVisiteCollectionResponse->getErrorReason());
    }
}
