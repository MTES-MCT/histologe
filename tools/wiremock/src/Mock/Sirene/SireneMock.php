<?php

namespace Mock\Sirene;

use Mock\AppMock;
use WireMock\Client\WireMock;

class SireneMock
{
    protected const string RESOURCES_DIR = 'Sirene/';
    protected const string CONTENT_TYPE = 'application/json';

    public static function prepare(WireMock $wireMock): void
    {
        $responseSiret = json_decode(AppMock::getMockContent(
            self::RESOURCES_DIR.'siret.json'
        ));

        $wireMock->stubFor(
            WireMock::get(WireMock::urlMatching('/api-sirene/3.11/siret/.*'))
                ->willReturn(
                    WireMock::aResponse()
                        ->withStatus(200)
                        ->withHeader('Content-Type', self::CONTENT_TYPE)
                        ->withBody(json_encode($responseSiret))
                )
        );
    }
}
