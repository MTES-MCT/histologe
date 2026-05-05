<?php

namespace App\Tests\Unit\Service\Esabora\Response;

use App\Service\Interconnection\Esabora\Response\DossierStateSCHSResponse;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;

class DossierStateSCHSResponseTest extends TestCase
{
    public function testDossierResponseSuccessfullyCreated(): void
    {
        $filepath = __DIR__.'/../../../../../tools/wiremock/src/Resources/Esabora/schs/ws_etat_dossier_sas/etat_importe.json';
        $responseEsabora = json_decode((string) file_get_contents($filepath), true);

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

    public function testDossierResponseWithoutDossID(): void
    {
        $filepath = __DIR__.'/../../../../../tools/wiremock/src/Resources/Esabora/schs/ws_etat_dossier_sas/etat_importe_missing_column.json';
        $responseEsabora = json_decode((string) file_get_contents($filepath), true);

        $dossierResponse = new DossierStateSCHSResponse($responseEsabora, 200);
        $this->assertEquals('00000000-0000-0000-2022-000000000011', $dossierResponse->getSasReference());
        $this->assertEquals('Importé', $dossierResponse->getSasEtat());
        $this->assertNull($dossierResponse->getId());
        $this->assertEquals('2026DSP-19349-HAB', $dossierResponse->getNumero());
        $this->assertEquals('A traiter', $dossierResponse->getStatutAbrege());
        $this->assertEquals('A traiter', $dossierResponse->getStatut());
        $this->assertEquals('en cours', $dossierResponse->getEtat());
        $this->assertNull($dossierResponse->getDateCloture());
        $this->assertNull($dossierResponse->getErrorReason());
    }

    public function testDossierResponseDifferentOrder(): void
    {
        $responseEsabora = [
            'columnList' => [
                'Doss_Statut',
                'SAS_Référence',
                'Doss_ID',
                'SAS_Etat',
            ],
            'rowList' => [
                [
                    'columnDataList' => [
                        'A traiter',
                        'REF123',
                        '12345',
                        'Importé',
                    ],
                ],
            ],
        ];

        $dossierResponse = new DossierStateSCHSResponse($responseEsabora, 200);
        $this->assertEquals('REF123', $dossierResponse->getSasReference());
        $this->assertEquals('Importé', $dossierResponse->getSasEtat());
        $this->assertEquals('12345', $dossierResponse->getId());
        $this->assertEquals('A traiter', $dossierResponse->getStatut());
        $this->assertNull($dossierResponse->getErrorReason());
    }

    public function testDossierResponseInvalidColumnCount(): void
    {
        $responseEsabora = [
            'columnList' => [
                'SAS_Référence',
                'SAS_Etat',
            ],
            'rowList' => [
                [
                    'columnDataList' => [
                        'REF123',
                        'Importé',
                        'Extra',
                    ],
                ],
            ],
        ];

        $dossierResponse = new DossierStateSCHSResponse($responseEsabora, 200);
        $this->assertNull($dossierResponse->getSasReference());
        $this->assertEquals('Nombre de colonnes et de données incohérent', $dossierResponse->getErrorReason());
    }
}
