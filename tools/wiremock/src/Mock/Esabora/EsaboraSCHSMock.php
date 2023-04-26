<?php

namespace Mock\Esabora;

use WireMock\Client\WireMock;

class EsaboraSCHSMock extends AbstractEsaboraMock
{
    protected const BASE_PATH = '/ws/rest';
    protected const RESOURCES_DIR = 'Esabora/schs/';
    protected const REQUEST_SEARCH_NAME = 'WS_ETAT_DOSSIER_SAS';

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

        self::createPushDossierMock(
            $wiremock,
            'doSearch',
            'WS_ETAT_SAS',
            'ws_etat_sas.json',
            self::BASE_PATH,
            self::RESOURCES_DIR,
            self::REQUEST_SEARCH_NAME
        );

        self::createStateDossierMock(
            $wiremock,
            'doSearch',
            WireMock::matchingJsonPath(
                '$.criterionList[0].criterionValueList[0]',
                WireMock::equalTo('00000000-0000-0000-2022-000000000008')
            ),
            'ws_etat_dossier_sas/etat_a_traiter.json',
            self::BASE_PATH,
            self::RESOURCES_DIR,
            self::REQUEST_SEARCH_NAME
        );

        self::createStateDossierMock(
            $wiremock,
            'doSearch',
            WireMock::matchingJsonPath(
                '$.criterionList[0].criterionValueList[0]',
                WireMock::equalTo('00000000-0000-0000-2022-000000000001')
            ),
            'ws_etat_dossier_sas/etat_importe.json',
            self::BASE_PATH,
            self::RESOURCES_DIR,
            self::REQUEST_SEARCH_NAME
        );

        self::createStateDossierMock(
            $wiremock,
            'doSearch',
            WireMock::matchingJsonPath(
                '$.criterionList[0].criterionValueList[0]',
                WireMock::equalTo('00000000-0000-0000-2022-000000000002')
            ),
            'ws_etat_dossier_sas/etat_non_importe.json',
            self::BASE_PATH,
            self::RESOURCES_DIR,
            self::REQUEST_SEARCH_NAME
        );

        self::createStateDossierMock(
            $wiremock,
            'doSearch',
            WireMock::matchingJsonPath(
                '$.criterionList[0].criterionValueList[0]',
                WireMock::equalTo('00000000-0000-0000-2022-000000000010')
            ),
            'ws_etat_dossier_sas/etat_termine.json',
            self::BASE_PATH,
            self::RESOURCES_DIR,
            self::REQUEST_SEARCH_NAME
        );

        self::createStateDossierMock(
            $wiremock,
            'doSearch',
            WireMock::matchingJsonPath(
                '$.criterionList[0].criterionValueList[0]',
                WireMock::notMatching('00000000-0000-0000-2022-000000000001|00000000-0000-0000-2022-000000000002|00000000-0000-0000-2022-000000000010|00000000-0000-0000-2022-000000000008')
            ),
            'ws_etat_dossier_sas/etat_non_trouve.json',
            self::BASE_PATH,
            self::RESOURCES_DIR,
            self::REQUEST_SEARCH_NAME
        );
    }
}
