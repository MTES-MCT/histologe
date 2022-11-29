<?php

namespace Mock\Esabora;

use Mock\AppMock;
use WireMock\Client\WireMock;

class EsaboraMock
{
    private const BASE_PATH = '/ws/rest';
    private const REQUEST_CONTENT_TYPE = 'application/json';
    private const REQUEST_AUTHORIZATION = 'Bearer';
    private const RESPONSE_CONTENT_TYPE = 'application/json';
    private const RESOURCES_DIR = 'Esabora/';

    public static function prepareMockForEsabora(WireMock $wiremock)
    {
        /* WS Import Histologe */
        $wiremock->stubFor(
            WireMock::post(WireMock::urlMatching(self::BASE_PATH.'/modbdd\\?task=doTreatment'))
                ->withHeader('Authorization', WireMock::containing(self::REQUEST_AUTHORIZATION))
                ->withHeader('Content-Type', WireMock::containing(self::REQUEST_CONTENT_TYPE))
                ->withRequestBody(WireMock::matchingJsonPath('$.treatmentName', WireMock::equalTo('Import Histologe')))
                ->willReturn(
                    WireMock::aResponse()
                        ->withStatus(200)
                        ->withHeader('Content-Type', self::RESPONSE_CONTENT_TYPE)
                        ->withBody(AppMock::getMockContent(self::RESOURCES_DIR.'ws_import.json'))
                )
        );

        /* WS Etat SAS */
        $wiremock->stubFor(
            WireMock::post(WireMock::urlMatching(self::BASE_PATH.'/mult\\?task=doSearch'))
                ->withHeader('Authorization', WireMock::containing(self::REQUEST_AUTHORIZATION))
                ->withHeader('Content-Type', WireMock::containing(self::REQUEST_CONTENT_TYPE))
                ->withRequestBody(WireMock::matchingJsonPath('$.searchName', WireMock::equalTo('WS_ETAT_SAS')))
                ->willReturn(
                    WireMock::aResponse()
                        ->withStatus(200)
                        ->withHeader('Content-Type', self::RESPONSE_CONTENT_TYPE)
                        ->withBody(AppMock::getMockContent(self::RESOURCES_DIR.'ws_etat_sas.json'))
                )
        );

        /* WS Etat Dossier SAS */
        $wiremock->stubFor(
            WireMock::post(WireMock::urlMatching(self::BASE_PATH.'/mult\\?task=doSearch'))
                ->withHeader('Authorization', WireMock::containing(self::REQUEST_AUTHORIZATION))
                ->withHeader('Content-Type', WireMock::containing(self::REQUEST_CONTENT_TYPE))
                ->withRequestBody(WireMock::matchingJsonPath('$.searchName', WireMock::equalTo('WS_ETAT_DOSSIER_SAS')))
                ->willReturn(
                    WireMock::aResponse()
                        ->withStatus(200)
                        ->withHeader('Content-Type', self::RESPONSE_CONTENT_TYPE)
                        ->withBody(AppMock::getMockContent(self::RESOURCES_DIR.'ws_etat_dossier_sas.json'))
                )
        );
    }
}
