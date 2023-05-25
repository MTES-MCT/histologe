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
    protected const MATCH_JSON_PATH = '$.criterionList[0].criterionValueList[0]';
    protected const SIGNALEMENT_SUBCRIBED_SISH = '00000000-0000-0000-2023-000000000010';
    protected const SIGNALEMENT_SUBCRIBED_SISH_SCHS = '00000000-0000-0000-2023-000000000012';

    protected static function createPushDossierMock(
        WireMock $wiremock,
        string $task,
        string $service,
        string $response,
        ?string $basePath,
        ?string $resourcesDir,
    ): void {
        $wiremock->stubFor(
            WireMock::post(WireMock::urlMatching($basePath.'/modbdd/\\?task='.$task))
                ->withHeader('Authorization', WireMock::containing(self::REQUEST_AUTHORIZATION))
                ->withHeader('Content-Type', WireMock::containing(self::REQUEST_CONTENT_TYPE))
                ->withRequestBody(WireMock::matchingJsonPath('$.treatmentName', WireMock::equalTo($service)))
                ->willReturn(
                    WireMock::aResponse()
                        ->withStatus(200)
                        ->withHeader('Content-Type', self::RESPONSE_CONTENT_TYPE)
                        ->withBody(AppMock::getMockContent($resourcesDir.$response))
                )
        );
    }

    protected static function createSearchDossierMock(
        WireMock $wiremock,
        string $task,
        string $service,
        JsonPathValueMatchingStrategy $body,
        string $response,
        ?string $basePath,
        ?string $resourcesDir,
    ): void {
        $wiremock->stubFor(
            WireMock::post(WireMock::urlMatching($basePath.'/mult/\\?task='.$task))
                ->withHeader('Authorization', WireMock::containing(self::REQUEST_AUTHORIZATION))
                ->withHeader('Content-Type', WireMock::containing(self::REQUEST_CONTENT_TYPE))
                ->withRequestBody(WireMock::matchingJsonPath(
                    '$.searchName',
                    WireMock::equalTo($service))
                )
                ->withRequestBody($body)
                ->willReturn(
                    WireMock::aResponse()
                        ->withStatus(200)
                        ->withHeader('Content-Type', self::RESPONSE_CONTENT_TYPE)
                        ->withBody(AppMock::getMockContent($resourcesDir.$response))
                )
        );
    }
}
