<?php

namespace Mock\Esabora;

use WireMock\Client\WireMock;

class EsaboraSISHMock extends AbstractEsaboraMock
{
    protected const BASE_PATH = '/ARS/ws/rest';
    protected const RESOURCES_DIR = 'Esabora/sish/';
    protected const SISH_ETAT_DOSSIER_SAS = 'SISH_ETAT_DOSSIER_SAS';
    protected const SISH_VISITES_DOSSIER_SAS = 'SISH_VISITES_DOSSIER_SAS';
    protected const SISH_ARRETES_DOSSIER_SAS = 'SISH_ARRETES_DOSSIER_SAS';

    public static function prepareMockForEsabora(WireMock $wiremock): void
    {
        self::createPushDossierMock(
            $wiremock,
            'doTreatment',
            'SISH_ADRESSE',
            'ws_dossier_adresse.json',
            self::BASE_PATH,
            self::RESOURCES_DIR,
        );

        self::createPushDossierMock(
            $wiremock,
            'doTreatment',
            'SISH_DOSSIER',
            'ws_dossier.json',
            self::BASE_PATH,
            self::RESOURCES_DIR,
        );

        self::createPushDossierMock(
            $wiremock,
            'doTreatment',
            'SISH_DOSSIER_PERSONNE',
            'ws_dossier_personne.json',
            self::BASE_PATH,
            self::RESOURCES_DIR,
        );

        self::createSearchDossierMock(
            $wiremock,
            'doSearch',
            self::SISH_ETAT_DOSSIER_SAS,
            WireMock::matchingJsonPath(
                '$.criterionList[0].criterionValueList[0]',
                WireMock::equalTo('00000000-0000-0000-2023-000000000010')
            ),
            'ws_etat_dossier_sas/etat_importe.json',
            self::BASE_PATH,
            self::RESOURCES_DIR,
        );

        self::createSearchDossierMock(
            $wiremock,
            'doSearch',
            self::SISH_ETAT_DOSSIER_SAS,
            WireMock::matchingJsonPath(
                '$.criterionList[0].criterionValueList[0]',
                WireMock::equalTo('00000000-0000-0000-2023-000000000012')
            ),
            'ws_etat_dossier_sas/etat_termine.json',
            self::BASE_PATH,
            self::RESOURCES_DIR,
        );

        self::createSearchDossierMock(
            $wiremock,
            'doSearch',
            self::SISH_VISITES_DOSSIER_SAS,
            WireMock::matchingJsonPath(
                '$.criterionList[0].criterionValueList[0]',
                WireMock::equalTo('00000000-0000-0000-2023-000000000010')
            ),
            'ws_visites_dossier_sas.json',
            self::BASE_PATH,
            self::RESOURCES_DIR,
        );

        self::createSearchDossierMock(
            $wiremock,
            'doSearch',
            self::SISH_ARRETES_DOSSIER_SAS,
            WireMock::matchingJsonPath(
                '$.criterionList[0].criterionValueList[0]',
                WireMock::equalTo('00000000-0000-0000-2023-000000000010')
            ),
            'ws_arretes_dossier_sas.json',
            self::BASE_PATH,
            self::RESOURCES_DIR,
        );

        self::createSearchDossierMock(
            $wiremock,
            'doSearch',
            self::SISH_VISITES_DOSSIER_SAS,
            WireMock::matchingJsonPath(
                '$.criterionList[0].criterionValueList[0]',
                WireMock::equalTo('00000000-0000-0000-2023-000000000012')
            ),
            'ws_visites_dossier_sas_en_cours.json',
            self::BASE_PATH,
            self::RESOURCES_DIR,
        );

        self::createSearchDossierMock(
            $wiremock,
            'doSearch',
            self::SISH_ARRETES_DOSSIER_SAS,
            WireMock::matchingJsonPath(
                '$.criterionList[0].criterionValueList[0]',
                WireMock::equalTo('00000000-0000-0000-2023-000000000012')
            ),
            'ws_arretes_dossier_sas_termine.json',
            self::BASE_PATH,
            self::RESOURCES_DIR,
        );
    }
}
