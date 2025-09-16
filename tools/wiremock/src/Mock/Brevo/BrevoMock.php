<?php

namespace Mock\Brevo;

use Mock\AppMock;
use WireMock\Client\WireMock;
use WireMock\PostServe\WebhookDefinition;

class BrevoMock
{
    protected const string RESOURCES_DIR = 'Brevo/';
    protected const string WEBHOOK_URL = 'http://signal_logement_nginx/webhook/brevo';

    protected const string CONTENT_TYPE = 'application/json';

    public static function prepare(WireMock $wireMock): void
    {
        $brevoEvents = [
            'blocked',
            'hard_bounce',
            'soft_bounce',
            'spam',
            'invalid_email',
            'delivered',
        ];

        foreach ($brevoEvents as $event) {
            $body = AppMock::getMockContent(self::RESOURCES_DIR."/$event.json");

            $webhookDefinition = (new WebhookDefinition())
                ->withMethod('POST')
                ->withUrl(self::WEBHOOK_URL)
                ->withHeader('Content-Type', 'application/json')
                ->withBody($body);

            $wireMock->stubFor(
                WireMock::post(WireMock::urlMatching("/brevo/trigger/$event"))
                    ->withHeader('Content-Type', WireMock::containing('application/json'))
                    ->willReturn(
                        WireMock::aResponse()
                            ->withStatus(202)
                            ->withHeader('Content-Type', self::CONTENT_TYPE)
                            ->withBody('{"response": "OK"}')
                    )->withPostServeAction('webhook', $webhookDefinition)
            );
        }
    }
}
