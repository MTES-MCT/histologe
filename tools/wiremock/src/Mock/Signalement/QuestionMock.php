<?php

namespace Mock\Signalement;

use Mock\AppMock;
use WireMock\Client\WireMock;

class QuestionMock
{
    protected const RESOURCES_DIR = '/Signalement';
    protected const BASE_PATH = '/api';
    protected const RESPONSE_CONTENT_TYPE = 'application/json';

    public static function prepareMockForQuestion(WireMock $wireMock): void
    {
        $wireMock->stubFor(
            WireMock::get(
                WireMock::urlMatching(self::BASE_PATH.'/questions\\?profil=tous')
            )
            ->willReturn(
                WireMock::aResponse()
                ->withStatus(200)
                ->withHeader('Content-Type', self::RESPONSE_CONTENT_TYPE)
                ->withHeader('Access-Control-Allow-Origin', '*')
                ->withBody(AppMock::getMockContent(self::RESOURCES_DIR.'/questions_profile_tous.json'))
            )
        );
    }
}
