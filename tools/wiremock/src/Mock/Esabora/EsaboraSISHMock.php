<?php

namespace Mock\Esabora;

use WireMock\Client\WireMock;

class EsaboraSISHMock extends AbstractEsaboraMock
{
    protected const BASE_PATH = '/ARS/ws/rest';
    protected const RESOURCES_DIR = 'Esabora/sish/';
    protected const REQUEST_SEARCH_NAME = 'SISH_ETAT_DOSSIER_SAS';

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
    }
}
