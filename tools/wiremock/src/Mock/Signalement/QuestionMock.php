<?php

namespace Mock\Signalement;

use Mock\AppMock;
use WireMock\Client\WireMock;

class QuestionMock
{
    protected const RESOURCES_DIR = '/Signalement';
    protected const BASE_PATH = '/api';
    protected const RESPONSE_CONTENT_TYPE = 'application/json';

    public static function prepareMockForQuestionAllProfiles(WireMock $wireMock): void
    {
        self::createApiStub($wireMock, 'profil=tous', 'questions_profile_tous.json');
        self::createApiStub($wireMock, 'profil=locataire', 'questions_profile_locataire.json');
        self::createApiStub($wireMock, 'profil=bailleur-occupant', 'questions_profile_bailleur_occupant.json');
        self::createApiStub($wireMock, 'profil=tiers-particulier', 'questions_profile_tiers_particulier.json');
        self::createApiStub($wireMock, 'profil=tiers-pro', 'questions_profile_tiers_pro.json');
        self::createApiStub($wireMock, 'profil=service-de-secours', 'questions_profile_service_secours.json');
        self::createApiStub($wireMock, 'profil=bailleur', 'questions_profile_bailleur.json');
    }

    public static function createApiStub(WireMock $wireMock, string $queryParameter, string $jsonFileResponse): void
    {
        $wireMock->stubFor(
            WireMock::get(WireMock::urlMatching(self::BASE_PATH.'/questions\\?'.$queryParameter))
                ->willReturn(
                    WireMock::aResponse()
                        ->withStatus(200)
                        ->withHeader('Content-Type', self::RESPONSE_CONTENT_TYPE)
                        ->withHeader('Access-Control-Allow-Origin', '*')
                        ->withBody(AppMock::getMockContent(self::RESOURCES_DIR.'/'. $jsonFileResponse))
                )
        );
    }
}
