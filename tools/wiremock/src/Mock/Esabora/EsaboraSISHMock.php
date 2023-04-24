<?php

namespace Mock\Esabora;

use Mock\AppMock;
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
        self::createMock($wiremock, 'SISH_ADRESSE', 'ws_dossier_adresse.json');
        self::createMock($wiremock, 'SISH_DOSSIER', 'ws_dossier.json');
        self::createMock($wiremock, 'SISH_DOSSIER_PERSONNE', 'ws_dossier_personne.json');
    }

    private static function createMock(WireMock $wiremock, string $service, string $response): void
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
}
