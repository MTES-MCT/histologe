<?php

namespace Mock\Esabora;

use WireMock\Client\WireMock;

class EsaboraSISHMock extends AbstractEsaboraMock
{
    protected const string BASE_PATH = '/ARS/ws/rest';
    protected const string RESOURCES_DIR = 'Esabora/sish/';
    protected const string SISH_ETAT_DOSSIER_SAS = 'SISH_ETAT_DOSSIER_SAS';
    protected const string SISH_VISITES_DOSSIER_SAS = 'SISH_VISITES_DOSSIER_SAS';
    protected const string SISH_ARRETES_DOSSIER_SAS = 'SISH_ARRETES_DOSSIER_SAS';

    public static function prepareMockForEsabora(WireMock $wiremock): void
    {
        self::createPushDossierMock(
            $wiremock,
            'doTreatment',
            'SISH_ADRESSE',
            'error/ws_dossier_adresse.json',
            400,
            '$.fieldList[?(@.fieldName == "Reference_Adresse" && @.fieldValue == "00000000-0000-0000-2023-000000000020")]',
            self::BASE_PATH,
            self::RESOURCES_DIR,
        );

        self::createPushDossierMock(
            $wiremock,
            'doTreatment',
            'SISH_ADRESSE',
            'ws_dossier_adresse.json',
            200,
            '$.fieldList[?(@.fieldName == "Reference_Adresse" && @.fieldValue != "00000000-0000-0000-2023-000000000020")]',
            self::BASE_PATH,
            self::RESOURCES_DIR,
        );

        self::createPushDossierMock(
            $wiremock,
            'doTreatment',
            'SISH_DOSSIER',
            'ws_dossier.json',
            200,
            null,
            self::BASE_PATH,
            self::RESOURCES_DIR,
        );

        self::createPushDossierMock(
            $wiremock,
            'doTreatment',
            'SISH_DOSSIER_PERSONNE',
            'ws_dossier_personne.json',
            200,
            null,
            self::BASE_PATH,
            self::RESOURCES_DIR,
        );

        self::createSearchDossierMock(
            $wiremock,
            'doSearch',
            self::SISH_ETAT_DOSSIER_SAS,
            WireMock::matchingJsonPath(
                self::MATCH_JSON_PATH,
                WireMock::equalTo(self::SIGNALEMENT_SUBSCRIBED_SISH)
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
                WireMock::equalTo('00000000-0000-0000-2023-000000000020')
            ),
            'ws_etat_dossier_sas/etat_importe_sish_schs.json',
            self::BASE_PATH,
            self::RESOURCES_DIR,
        );

        self::createSearchDossierMock(
            $wiremock,
            'doSearch',
            self::SISH_ETAT_DOSSIER_SAS,
            WireMock::matchingJsonPath(
                self::MATCH_JSON_PATH,
                WireMock::equalTo('00000000-0000-0000-2023-000000000013')
            ),
            'ws_etat_dossier_sas/etat_rejete.json',
            self::BASE_PATH,
            self::RESOURCES_DIR,
        );

        self::createSearchDossierMock(
            $wiremock,
            'doSearch',
            self::SISH_ETAT_DOSSIER_SAS,
            WireMock::matchingJsonPath(
                self::MATCH_JSON_PATH,
                WireMock::equalTo(self::SIGNALEMENT_SUBSCRIBED_SISH_SCHS)
            ),
            'ws_etat_dossier_sas/etat_termine.json',
            self::BASE_PATH,
            self::RESOURCES_DIR,
        );

        self::createMockIntervention(
            $wiremock,
            self::SISH_VISITES_DOSSIER_SAS,
            'ws_visites_dossier_sas.json',
            self::SIGNALEMENT_SUBSCRIBED_SISH
        );

        self::createMockIntervention(
            $wiremock,
            self::SISH_ARRETES_DOSSIER_SAS,
            'ws_arretes_dossier_sas.json',
            self::SIGNALEMENT_SUBSCRIBED_SISH
        );

        self::createMockIntervention(
            $wiremock,
            self::SISH_VISITES_DOSSIER_SAS,
            'ws_visites_dossier_sas_en_cours.json'
        );

        self::createMockIntervention(
            $wiremock,
            self::SISH_VISITES_DOSSIER_SAS,
            'ws_visites_dossier_sas_en_cours_sish_schs.json',
            '00000000-0000-0000-2023-000000000020'
        );

        self::createMockIntervention(
            $wiremock,
            self::SISH_ARRETES_DOSSIER_SAS,
            'ws_arretes_dossier_sas_termine.json',
            '00000000-0000-0000-2023-000000000010'
        );

        self::createMockIntervention(
            $wiremock,
            self::SISH_ARRETES_DOSSIER_SAS,
            'ws_arrete_main_levee_dossier_sas.json',
            '00000000-0000-0000-2023-000000000120'
        );
    }

    protected static function createMockIntervention(
        WireMock $wiremock,
        string $service,
        string $response,
        string $uuidSignalement = self::SIGNALEMENT_SUBSCRIBED_SISH_SCHS,
    ): void {
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
