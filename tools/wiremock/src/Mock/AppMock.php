<?php

namespace Mock;

include_once __DIR__.'/../../vendor/autoload.php';

use Mock\Esabora\EsaboraMock;
use WireMock\Client\WireMock;

class AppMock
{
    private const RESOURCES_DIR = __DIR__.'./../Resources/';

    public static function init(): void
    {
        try {
            $wireMock = WireMock::create(getenv('WIREMOCK_HOSTNAME'), getenv('WIREMOCK_PORT'));
            EsaboraMock::prepareMockForEsabora($wireMock);
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
