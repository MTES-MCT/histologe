<?php

namespace Mock\Esabora;

use Mock\AppMock;
use WireMock\Client\JsonPathValueMatchingStrategy;
use WireMock\Client\WireMock;

abstract class AbstractEsaboraMock
{
    protected const REQUEST_CONTENT_TYPE = 'application/json';
    protected const REQUEST_AUTHORIZATION = 'Bearer';
    protected const RESPONSE_CONTENT_TYPE = self::REQUEST_CONTENT_TYPE;
    protected const BASE_PATH = '';
    protected const RESOURCES_DIR = '';
    protected const REQUEST_SEARCH_NAME = '';

    protected static function createPushDossierMock(
        WireMock $wiremock,
        string $task,
        string $service,
        string $response
    ): void {
        $wiremock->stubFor(
            WireMock::post(WireMock::urlMatching(self::BASE_PATH.'/modbdd/\\?task='.$task))
                ->withHeader('Authorization', WireMock::containing(self::REQUEST_AUTHORIZATION))
                ->withHeader('Content-Type', WireMock::containing(self::REQUEST_CONTENT_TYPE))
                ->withRequestBody(WireMock::matchingJsonPath('$.treatmentName', WireMock::equalTo($service)))
                ->willReturn(
                    WireMock::aResponse()
                        ->withStatus(200)
                        ->withHeader('Content-Type', self::RESPONSE_CONTENT_TYPE)
                        ->withBody(AppMock::getMockContent(static::RESOURCES_DIR.$response))
                )
        );
    }

    protected static function createStateDossierMock(
        WireMock $wiremock,
        string $task,
        JsonPathValueMatchingStrategy $body,
        string $response
    ): void {
        $wiremock->stubFor(
            WireMock::post(WireMock::urlMatching(self::BASE_PATH.'/mult/\\?task='.$task))
                ->withHeader('Authorization', WireMock::containing(self::REQUEST_AUTHORIZATION))
                ->withHeader('Content-Type', WireMock::containing(self::REQUEST_CONTENT_TYPE))
                ->withRequestBody(WireMock::matchingJsonPath(
                    '$.searchName',
                    WireMock::equalTo(self::REQUEST_SEARCH_NAME))
                )
                ->withRequestBody($body)
                ->willReturn(
                    WireMock::aResponse()
                        ->withStatus(200)
                        ->withHeader('Content-Type', self::RESPONSE_CONTENT_TYPE)
                        ->withBody(AppMock::getMockContent(static::RESOURCES_DIR.$response))
                )
        );
    }
}
