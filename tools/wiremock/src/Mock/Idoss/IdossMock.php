<?php

namespace Mock\Idoss;

use Mock\AppMock;
use WireMock\Client\WireMock;

class IdossMock
{
    protected const CONTENT_TYPE = 'application/json';
    protected const REQUEST_AUTHORIZATION = 'Bearer';

    protected const RESOURCES_DIR = 'Idoss/';

    public static function prepareMockForIdoss(WireMock $wireMock): void
    {
        $response = json_decode(AppMock::getMockContent(
            self::RESOURCES_DIR.'authentification.json'
        ), true);
        $response['expirationDate'] = (new \DateTimeImmutable())
            ->modify('+1 day')
            ->format('Y-m-d\TH:i:s.v\Z');
        $wireMock->stubFor(
            WireMock::post(WireMock::urlMatching('/idoss/api/Utilisateur/authentification'))
                ->withHeader('Content-Type', WireMock::containing(self::CONTENT_TYPE))
                ->withRequestBody(WireMock::matchingJsonPath('$.username'))
                ->withRequestBody(WireMock::matchingJsonPath('$.password'))
                ->willReturn(
                    WireMock::aResponse()
                        ->withStatus(200)
                        ->withHeader('Content-Type', self::CONTENT_TYPE)
                        ->withBody(json_encode($response))
                )
        );

        self::pushDossier(
            $wireMock,
            '00000000-0000-0000-2023-000000000009',
            'creatDossHistologe-2023-9.json'
        );

        self::pushDossier(
            $wireMock,
            '00000000-0000-0000-2023-000000000012',
            'creatDossHistologe-2023-12.json'
        );

        self::pushDossier(
            $wireMock,
            '00000000-0000-0000-2023-000000000013',
            'creatDossHistologe-2023-13.json'
        );

        self::pushDossier(
            $wireMock,
            '00000000-0000-0000-2023-000000000014',
            'creatDossHistologe-2023-14.json'
        );

        self::pushDossier(
            $wireMock,
            '00000000-0000-0000-2023-000000000015',
            'creatDossHistologe-2023-15.json'
        );

        $wireMock->stubFor(
            WireMock::get(WireMock::urlMatching('/idoss/api/EtatCivil/listStatutsHistologe'))
                ->withHeader('Authorization', WireMock::containing(self::REQUEST_AUTHORIZATION))
                ->willReturn(
                    WireMock::aResponse()
                        ->withStatus(200)
                        ->withHeader('Content-Type', self::CONTENT_TYPE)
                        ->withBody(AppMock::getMockContent(self::RESOURCES_DIR.'listStatutsHistologe.json'))
                )
        );

        $wireMock->stubFor(
            WireMock::post(WireMock::urlMatching('/idoss/api/EtatCivil/uploadFileRepoHistologe'))
                ->withHeader('Content-Type', WireMock::matching('multipart/form-data;.*'))
                ->withRequestBody(WireMock::matching(
                    '.*Content-Disposition: form-data; name="id".*')
                )
                ->withRequestBody(WireMock::matching(
                    '.*Content-Disposition: form-data; name="uuid".*')
                )
                ->withRequestBody(WireMock::matching(
                    '.*Content-Disposition: form-data; name="file"; filename=".*".*')
                )
                ->willReturn(
                    WireMock::aResponse()
                        ->withStatus(200)
                        ->withHeader('Content-Type', self::CONTENT_TYPE)
                        ->withBody(AppMock::getMockContent(self::RESOURCES_DIR.'uploadFileRepoHistologe.json'))
                )
        );
    }

    private static function pushDossier(WireMock $wireMock, string $uuidSignalement, string $resourceResponseFile): void
    {
        $wireMock->stubFor(
            WireMock::post(WireMock::urlMatching('/idoss/api/EtatCivil/creatDossHistologe'))
                ->withHeader('Content-Type', WireMock::containing(self::CONTENT_TYPE))
                ->withHeader('Authorization', WireMock::containing(self::REQUEST_AUTHORIZATION))
                ->withRequestBody(WireMock::matchingJsonPath('$.Dossier.UUIDSignalement',
                    WireMock::equalTo($uuidSignalement)
                ))
                ->willReturn(
                    WireMock::aResponse()
                        ->withStatus(200)
                        ->withHeader('Content-Type', self::CONTENT_TYPE)
                        ->withBody(AppMock::getMockContent(self::RESOURCES_DIR.$resourceResponseFile))
                )
        );
    }
}
