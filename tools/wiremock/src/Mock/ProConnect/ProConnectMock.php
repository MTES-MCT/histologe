<?php

namespace Mock\ProConnect;

use Mock\AppMock;
use WireMock\Client\WireMock;

class ProConnectMock
{
    protected const string RESOURCES_DIR = 'ProConnect/';
    protected const string CONTENT_TYPE = 'application/json';

    public static function prepareAuthorizationMock(WireMock $wireMock): void
    {
        $responseOpenId = json_decode(AppMock::getMockContent(
            self::RESOURCES_DIR.'openid-configuration.json'
        ), true);

        $wireMock->stubFor(
            WireMock::get(WireMock::urlMatching('/api/v2/.well-known/openid-configuration'))
                ->willReturn(
                    WireMock::aResponse()
                        ->withStatus(200)
                        ->withHeader('Content-Type', self::CONTENT_TYPE)
                        ->withBody(json_encode($responseOpenId))
                )
        );

        $responseToken = json_decode(AppMock::getMockContent(
            self::RESOURCES_DIR.'token.json'
        ));

        $wireMock->stubFor(
            WireMock::post(WireMock::urlMatching('/proconnect/token'))
                ->withHeader('Content-Type', WireMock::containing('application/x-www-form-urlencoded'))
                ->willReturn(
                    WireMock::aResponse()
                        ->withStatus(200)
                        ->withHeader('Content-Type', self::CONTENT_TYPE)
                        ->withBody(json_encode($responseToken))
                )
        );

        $responseJwks = json_decode(AppMock::getMockContent(
            self::RESOURCES_DIR.'jwks.json'
        ));

        $wireMock->stubFor(
            WireMock::get(WireMock::urlMatching('/jwks'))
                ->willReturn(
                    WireMock::aResponse()
                        ->withStatus(200)
                        ->withHeader('Content-Type', self::CONTENT_TYPE)
                        ->withBody(json_encode($responseJwks))
                )
        );

        $wireMock->stubFor(
            WireMock::get(WireMock::urlPathEqualTo('/authorize'))
                ->willReturn(
                    WireMock::aResponse()
                        ->withStatus(302)
                        ->withHeader('Content-Type', self::CONTENT_TYPE)
                        ->withHeader('Location', 'http://localhost:8080/proconnect/login-callback?code=fake_code&state=fake_state')
                )
        );

        $wireMock->stubFor(
            WireMock::get(WireMock::urlPathEqualTo('/session/end'))
                ->withQueryParam('id_token_hint', WireMock::matching('.*'))
                ->withQueryParam('state', WireMock::matching('.*'))
                ->withQueryParam('post_logout_redirect_uri', WireMock::matching('.*'))
                ->willReturn(
                    WireMock::aResponse()
                        ->withStatus(302)
                        ->withHeader('Location', 'http://localhost:8080/logout')
                )
        );

        $responseUserInfo = AppMock::getMockContent(
            self::RESOURCES_DIR.'userinfo.txt'
        );

        $wireMock->stubFor(
            WireMock::get(WireMock::urlMatching('/userinfo'))
                ->willReturn(
                    WireMock::aResponse()
                        ->withStatus(200)
                        ->withHeader('Content-Type', self::CONTENT_TYPE)
                        ->withBody($responseUserInfo)
                )
        );
    }
}
