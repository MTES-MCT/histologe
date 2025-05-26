<?php

namespace Mock\Rial;

use Mock\AppMock;
use WireMock\Client\WireMock;

class RialMock
{
    protected const string RESOURCES_DIR = 'Rial/';

    protected const string CONTENT_TYPE = 'application/json';
    protected const string REQUEST_AUTHORIZATION = 'Bearer';

    public static function prepare(WireMock $wireMock): void
    {
        $responseToken = json_decode(AppMock::getMockContent(
            self::RESOURCES_DIR.'token.json'
        ));
        $wireMock->stubFor(
            WireMock::post(WireMock::urlMatching('/token'))
                ->withHeader('Content-Type', WireMock::containing('application/x-www-form-urlencoded'))
                ->willReturn(
                    WireMock::aResponse()
                        ->withStatus(200)
                        ->withHeader('Content-Type', self::CONTENT_TYPE)
                        ->withBody(json_encode($responseToken))
                )
        );

        $responseList = json_decode(AppMock::getMockContent(
            self::RESOURCES_DIR.'list.json'
        ));
        $wireMock->stubFor(
            WireMock::get(WireMock::urlMatching('/rial/v1/locaux/adressetopographique'))
                ->withHeader('Authorization', WireMock::containing(self::REQUEST_AUTHORIZATION))
                ->willReturn(
                    WireMock::aResponse()
                        ->withStatus(200)
                        ->withHeader('Content-Type', self::CONTENT_TYPE)
                        ->withBody(json_encode($responseList))
                )
        );
    }
}
