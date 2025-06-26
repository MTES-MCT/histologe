<?php

namespace App\Tests\Functional\Controller\Security;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class CspReportControllerTest extends WebTestCase
{
    public function testCspReportWithValidPayload(): void
    {
        $client = static::createClient();

        $payload = [
            'csp-report' => [
                'blocked-uri' => 'https://malicious-site.com',
                'violated-directive' => 'script-src',
                'document-uri' => 'https://histologe.fr',
                'original-policy' => "default-src 'self'; script-src 'self'",
                'source-file' => 'https://histologe.fr/js/app.js',
                'line-number' => 42,
                'column-number' => 10,
            ],
        ];

        $client->request(
            'POST',
            '/csp-report',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($payload)
        );

        $this->assertEquals(Response::HTTP_NO_CONTENT, $client->getResponse()->getStatusCode());
        $this->assertEmpty($client->getResponse()->getContent());
    }

    public function testCspReportWithGetMethod(): void
    {
        $client = static::createClient();

        $client->request('GET', '/csp-report');

        // La route n'accepte que POST, donc GET devrait retourner 405 Method Not Allowed
        $this->assertEquals(Response::HTTP_METHOD_NOT_ALLOWED, $client->getResponse()->getStatusCode());
    }
}
