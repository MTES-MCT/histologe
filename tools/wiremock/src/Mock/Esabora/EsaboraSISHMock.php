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
                self::MATCH_JSON_PATH,
                WireMock::equalTo(self::SIGNALEMENT_SUBCRIBED_SISH)
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
                self::MATCH_JSON_PATH,
                WireMock::equalTo(self::SIGNALEMENT_SUBCRIBED_SISH_SCHS)
            ),
            'ws_etat_dossier_sas/etat_termine.json',
            self::BASE_PATH,
            self::RESOURCES_DIR,
        );

        self::createMockIntervention(
            $wiremock,
            self::SISH_VISITES_DOSSIER_SAS,
            'ws_visites_dossier_sas.json',
            self::SIGNALEMENT_SUBCRIBED_SISH
        );

        self::createMockIntervention(
            $wiremock,
            self::SISH_ARRETES_DOSSIER_SAS,
            'ws_arretes_dossier_sas.json',
            self::SIGNALEMENT_SUBCRIBED_SISH
        );

        self::createMockIntervention(
            $wiremock,
            self::SISH_VISITES_DOSSIER_SAS,
            'ws_visites_dossier_sas_en_cours.json'
        );

        self::createMockIntervention(
            $wiremock,
            self::SISH_ARRETES_DOSSIER_SAS,
            'ws_arretes_dossier_sas_termine.json'
        );
    }

    protected static function createMockIntervention(
        Wiremock $wiremock,
        string $service,
        string $response,
        string $uuidSignalement = self::SIGNALEMENT_SUBCRIBED_SISH_SCHS
    ) {
        self::createSearchDossierMock(
            $wiremock,
            'doSearch',
            $service,
            WireMock::matchingJsonPath(
                self::MATCH_JSON_PATH,
                WireMock::equalTo($uuidSignalement)
            ),
            $response,
            self::BASE_PATH,
            self::RESOURCES_DIR,
        );
    }
}
