<?php

namespace Mock\Esabora;

use WireMock\Client\WireMock;

class EsaboraSCHSMock extends AbstractEsaboraMock
{
    protected const BASE_PATH = '/ws/rest';
    protected const RESOURCES_DIR = 'Esabora/schs/';
    protected const WS_ETAT_DOSSIER_SAS = 'WS_ETAT_DOSSIER_SAS';

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
}
