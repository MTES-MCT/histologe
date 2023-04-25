<?php

namespace Mock\Esabora;

use Mock\AppMock;
use WireMock\Client\JsonPathValueMatchingStrategy;
use WireMock\Client\WireMock;

class EsaboraSISHMock
{
    private const BASE_PATH = '/ARS/ws/rest';
    private const REQUEST_CONTENT_TYPE = 'application/json';
    private const REQUEST_AUTHORIZATION = 'Bearer';
    private const RESPONSE_CONTENT_TYPE = self::REQUEST_CONTENT_TYPE;
    private const RESOURCES_DIR = 'Esabora/sish/';

    public static function prepareMockForEsabora(WireMock $wiremock): void
    {
        self::createPushDossierMock($wiremock, 'SISH_ADRESSE', 'ws_dossier_adresse.json');
        self::createPushDossierMock($wiremock, 'SISH_DOSSIER', 'ws_dossier.json');
        self::createPushDossierMock($wiremock, 'SISH_DOSSIER_PERSONNE', 'ws_dossier_personne.json');

        self::createStateDossierMock(
            $wiremock,
            'doSearch',
            WireMock::matchingJsonPath(
                '$.criterionList[0].criterionValueList[0]',
                WireMock::equalTo('00000000-0000-0000-2022-000000000001')
            ),
            'ws_etat_dossier_sas/etat_importe.json'
        );
    }

    private static function createPushDossierMock(WireMock $wiremock, string $service, string $response): void
    {
        $wiremock->stubFor(
            WireMock::post(WireMock::urlMatching(self::BASE_PATH.'/modbdd/\\?task=doTreatment'))
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
                ->withRequestBody(WireMock::matchingJsonPath('$.searchName', WireMock::equalTo('SISH_ETAT_DOSSIER_SAS')))
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
