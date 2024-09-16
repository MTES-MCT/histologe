<?php

namespace Mock\Esabora;

use Mock\AppMock;
use WireMock\Client\WireMock;

class EsaboraSCHSMock extends AbstractEsaboraMock
{
    protected const BASE_PATH = '/ws/rest';
    protected const RESOURCES_DIR = 'Esabora/schs/';
    protected const WS_ETAT_DOSSIER_SAS = 'WS_ETAT_DOSSIER_SAS';
    protected const WS_EVT_DOSSIER_SAS = 'WS_EVT_DOSSIER_SAS';

    public static function prepareMockForEsabora(WireMock $wiremock): void
    {
        self::createPushDossierMock(
            $wiremock,
            'doTreatment',
            'Import HISTOLOGE',
            'ws_import.json',
            self::BASE_PATH,
            self::RESOURCES_DIR,
        );

        self::createCustomStateDossierMock(
            $wiremock,
            '00000000-0000-0000-2022-000000000008',
            'etat_a_traiter.json'
        );

        self::createCustomStateDossierMock(
            $wiremock,
            '00000000-0000-0000-2022-000000000001',
            'etat_importe.json'
        );

        self::createCustomStateDossierMock(
            $wiremock,
            '00000000-0000-0000-2022-000000000002',
            'etat_non_importe.json'
        );

        self::createCustomStateDossierMock(
            $wiremock,
            '00000000-0000-0000-2023-000000000009',
            'etat_termine.json'
        );

        self::createCustomStateDossierMock(
            $wiremock,
            self::SIGNALEMENT_SUBCRIBED_SISH_SCHS,
            'etat_termine.json'
        );

        $uuids = [
            '00000000-0000-0000-2022-000000000001',
            '00000000-0000-0000-2022-000000000002',
            '00000000-0000-0000-2022-000000000008',
            '00000000-0000-0000-2023-000000000009',
            self::SIGNALEMENT_SUBCRIBED_SISH_SCHS,
        ];

        self::createSearchDossierMock(
            $wiremock,
            'doSearch',
            self::WS_ETAT_DOSSIER_SAS,
            WireMock::matchingJsonPath(
                self::MATCH_JSON_PATH,
                WireMock::notMatching(implode('|', $uuids))
            ),
            'ws_etat_dossier_sas/etat_non_trouve.json',
            self::BASE_PATH,
            self::RESOURCES_DIR,
        );

        self::createSearchDossierMock(
            $wiremock,
            'doSearch',
            self::WS_EVT_DOSSIER_SAS,
            WireMock::matchingJsonPath(
                '$.searchName',
                WireMock::equalTo('WS_EVT_DOSSIER_SAS')
            ),
            'ws_get_dossier_events.json',
            self::BASE_PATH,
            self::RESOURCES_DIR,
        );

        self::createGetDocumentsMock(
            $wiremock,
            'getDocuments',
            '&searchId=\d+&documentTypeName=[a-zA-Z0-9._-]+&keyDataListList\[\d+\]\[\d+\]=\d+',
            'ws_get_dossier_event_files.json',
            self::BASE_PATH,
            self::RESOURCES_DIR,
        );
    }

    private static function createCustomStateDossierMock(WireMock $wiremock, string $uuid, string $jsonFilename): void
    {
        self::createSearchDossierMock(
            $wiremock,
            'doSearch',
            self::WS_ETAT_DOSSIER_SAS,
            WireMock::matchingJsonPath(
                self::MATCH_JSON_PATH,
                WireMock::equalTo($uuid)
            ),
            'ws_etat_dossier_sas/'.$jsonFilename,
            self::BASE_PATH,
            self::RESOURCES_DIR,
        );
    }

    protected static function createGetDocumentsMock(
        WireMock $wiremock,
        string $task,
        string $queryParameterRegex,
        string $response,
        ?string $basePath,
        ?string $resourcesDir,
    ): void {
        $url = $basePath
            .'/mult/\?task='
            .$task
            .$queryParameterRegex;
        $wiremock->stubFor(
            WireMock::post(WireMock::urlMatching($url))
                ->withHeader('Authorization', WireMock::containing(self::REQUEST_AUTHORIZATION))
                ->willReturn(
                    WireMock::aResponse()
                        ->withStatus(200)
                        ->withHeader('Content-Type', self::RESPONSE_CONTENT_TYPE)
                        ->withBody(AppMock::getMockContent($resourcesDir.$response))
                )
        );
    }
}
