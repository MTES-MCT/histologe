<?php

namespace Mock;

include_once __DIR__.'/../../vendor/autoload.php';

use Mock\Esabora\EsaboraSCHSMock;
use Mock\Esabora\EsaboraSISHMock;
use Mock\Idoss\IdossMock;
use Mock\ProConnect\ProConnectMock;
use WireMock\Client\WireMock;

class AppMock
{
    private const string RESOURCES_DIR = __DIR__.'./../Resources/';

    public static function init(): void
    {
        try {
            $wireMock = WireMock::create(getenv('WIREMOCK_HOSTNAME'), getenv('WIREMOCK_PORT'));
            EsaboraSCHSMock::prepareMockForEsabora($wireMock);
            EsaboraSISHMock::prepareMockForEsabora($wireMock);
            IdossMock::prepareMockForIdoss($wireMock);
            ProConnectMock::prepareAuthorizationMock($wireMock);
        } catch (\Throwable $exception) {
            printf('Error message: %s', $exception->getMessage());
        }
    }

    public static function getMockContent(string $filepath): string
    {
        return file_get_contents(self::RESOURCES_DIR.$filepath);
    }
}

AppMock::init();
