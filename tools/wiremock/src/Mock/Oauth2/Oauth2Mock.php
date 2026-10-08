<?php

namespace Mock\Oauth2;

use Mock\AppMock;
use WireMock\Client\WireMock;

class Oauth2Mock
{
    protected const string RESOURCES_DIR = 'Oauth2/';
    protected const string CONTENT_TYPE = 'application/json';
    protected const string CLIENT_ID = 'silooauth2';
    protected const string CLIENT_SECRET = 'lescanaris';

    public static function prepare(WireMock $wireMock): void
    {
        $responseToken = json_decode(AppMock::getMockContent(
            self::RESOURCES_DIR.'token.json'
        ));

        $wireMock->stubFor(
            WireMock::post(WireMock::urlMatching('/oauth2/token'))
                ->withHeader('Content-Type', WireMock::containing('application/x-www-form-urlencoded'))
                ->withBasicAuth(self::CLIENT_ID, self::CLIENT_SECRET)
                ->withRequestBody(WireMock::containing('grant_type=client_credentials'))
                ->withRequestBody(WireMock::containing('scope=openid'))
                ->willReturn(
                    WireMock::aResponse()
                        ->withStatus(200)
                        ->withHeader('Content-Type', self::CONTENT_TYPE)
                        ->withBody(json_encode($responseToken))
                )
        );
    }
}
