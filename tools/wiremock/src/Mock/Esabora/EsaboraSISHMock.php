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
            'ws_dossier_adresse.json'
        );

        self::createPushDossierMock(
            $wiremock,
            'doTreatment',
            'SISH_DOSSIER',
            'ws_dossier.json'
        );

        self::createPushDossierMock(
            $wiremock,
            'doTreatment',
            'SISH_DOSSIER_PERSONNE',
            'ws_dossier_personne.json'
        );
    }
}
