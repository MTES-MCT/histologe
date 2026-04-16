<?php

namespace Mock\Esabora;

use Mock\AppMock;
use WireMock\Client\JsonPathValueMatchingStrategy;
use WireMock\Client\WireMock;

abstract class AbstractEsaboraMock
{
    protected const string REQUEST_CONTENT_TYPE = 'application/json';
    protected const string REQUEST_AUTHORIZATION = 'Bearer';
    protected const string RESPONSE_CONTENT_TYPE = self::REQUEST_CONTENT_TYPE;
    protected const string MATCH_JSON_PATH = '$.criterionList[0].criterionValueList[0]';
    protected const string SIGNALEMENT_SUBSCRIBED_SISH = '00000000-0000-0000-2023-000000000010';
    protected const string SIGNALEMENT_SUBSCRIBED_SISH_SCHS = '00000000-0000-0000-2023-000000000012';

    protected static function createPushDossierMock(
        WireMock $wiremock,
        string $task,
        string $service,
        string $response,
        int $statusCode = 200,
        ?string $bodyMatcher = null,
        ?string $basePath = null,
        ?string $resourcesDir = null,
    ): void {
        $request = WireMock::post(WireMock::urlMatching($basePath.'/modbdd/\\?task='.$task))
            ->withHeader('Authorization', WireMock::containing(self::REQUEST_AUTHORIZATION))
            ->withHeader('Content-Type', WireMock::containing(self::REQUEST_CONTENT_TYPE))
            ->withRequestBody(WireMock::matchingJsonPath('$.treatmentName', WireMock::equalTo($service)));

        if (null !== $bodyMatcher) {
            $request->withRequestBody(WireMock::matchingJsonPath($bodyMatcher));
        }

        $wiremock->stubFor(
            $request->willReturn(
                WireMock::aResponse()
                    ->withStatus($statusCode)
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
