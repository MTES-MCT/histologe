<?php

namespace Mock\Esabora;

use Mock\AppMock;
use WireMock\Client\JsonPathValueMatchingStrategy;
use WireMock\Client\WireMock;

class EsaboraSCHSMock
{
    private const BASE_PATH = '/ws/rest';
    private const REQUEST_CONTENT_TYPE = 'application/json';
    private const REQUEST_AUTHORIZATION = 'Bearer';
    private const RESPONSE_CONTENT_TYPE = 'application/json';
    private const RESOURCES_DIR = 'Esabora/schs/';

    public static function prepareMockForEsabora(WireMock $wiremock): void
    {
        self::createPushDossierMock($wiremock, 'doTreatment', 'Import HISTOLOGE', 'ws_import.json');
        self::createPushDossierMock($wiremock, 'doSearch', 'WS_ETAT_SAS', 'ws_etat_sas.json');

        self::createStateDossierMock(
            $wiremock,
            'doSearch',
            WireMock::matchingJsonPath(
                '$.criterionList[0].criterionValueList[0]',
                WireMock::equalTo('00000000-0000-0000-2022-000000000008')
            ),
            'ws_etat_dossier_sas/etat_a_traiter.json'
        );

        self::createStateDossierMock(
            $wiremock,
            'doSearch',
            WireMock::matchingJsonPath(
                '$.criterionList[0].criterionValueList[0]',
                WireMock::equalTo('00000000-0000-0000-2022-000000000001')
            ),
            'ws_etat_dossier_sas/etat_importe.json'
        );

        self::createStateDossierMock(
            $wiremock,
            'doSearch',
            WireMock::matchingJsonPath(
                '$.criterionList[0].criterionValueList[0]',
                WireMock::equalTo('00000000-0000-0000-2022-000000000002')
            ),
            'ws_etat_dossier_sas/etat_non_importe.json'
        );

        self::createStateDossierMock(
            $wiremock,
            'doSearch',
            WireMock::matchingJsonPath(
                '$.criterionList[0].criterionValueList[0]',
                WireMock::equalTo('00000000-0000-0000-2022-000000000010')
            ),
            'ws_etat_dossier_sas/etat_termine.json'
        );

        self::createStateDossierMock(
            $wiremock,
            'doSearch',
            WireMock::matchingJsonPath(
                '$.criterionList[0].criterionValueList[0]',
                WireMock::notMatching('00000000-0000-0000-2022-000000000001|00000000-0000-0000-2022-000000000002|00000000-0000-0000-2022-000000000010|00000000-0000-0000-2022-000000000008')
            ),
            'ws_etat_dossier_sas/etat_non_trouve.json'
        );
    }

    private static function createPushDossierMock(WireMock $wiremock, string $task, string $service, string $response): void
    {
        $wiremock->stubFor(
            WireMock::post(WireMock::urlMatching(self::BASE_PATH.'/modbdd/\\?task='.$task))
                ->withHeader('Authorization', WireMock::containing(self::REQUEST_AUTHORIZATION))
                ->withHeader('Content-Type', WireMock::containing(self::REQUEST_CONTENT_TYPE))
                ->withRequestBody(WireMock::matchingJsonPath('$.treatmentName', WireMock::equalTo($service)))
                ->willReturn(
                    WireMock::aResponse()
                        ->withStatus(200)
                        ->withHeader('Content-Type', self::RESPONSE_CONTENT_TYPE)
                        ->withBody(AppMock::getMockContent(self::RESOURCES_DIR.$response))
                )
        );
    }

    private static function createStateDossierMock(
        WireMock $wiremock,
        string $task,
        JsonPathValueMatchingStrategy $body,
        string $response
    ): void {
        $wiremock->stubFor(
            WireMock::post(WireMock::urlMatching(self::BASE_PATH.'/mult/\\?task='.$task))
                ->withHeader('Authorization', WireMock::containing(self::REQUEST_AUTHORIZATION))
                ->withHeader('Content-Type', WireMock::containing(self::REQUEST_CONTENT_TYPE))
                ->withRequestBody(WireMock::matchingJsonPath('$.searchName', WireMock::equalTo('WS_ETAT_DOSSIER_SAS')))
                ->withRequestBody($body)
                ->willReturn(
                    WireMock::aResponse()
                        ->withStatus(200)
                        ->withHeader('Content-Type', self::RESPONSE_CONTENT_TYPE)
                        ->withBody(AppMock::getMockContent(self::RESOURCES_DIR.$response))
                )
        );
    }
}
