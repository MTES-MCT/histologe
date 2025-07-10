<?php

namespace App\Tests\Functional\Controller\Security;

use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class CspReportControllerTest extends WebTestCase
{
    /**
     * @param array<string, mixed> $payload
     *
     * @dataProvider provideCspReports
     */
    public function testCspReportWithValidPayload(
        array $payload,
        bool $shouldReport = false,
    ): void {
        $client = static::createClient();

        if ($shouldReport) {
            $loggerMock = $this->createMock(LoggerInterface::class);
            $loggerMock->expects($this->once())
                ->method('warning')

                ->with($this->stringContains('CSP Violation'));

            static::getContainer()->set(LoggerInterface::class, $loggerMock);
        }

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

    public static function provideCspReports(): \Generator
    {
        yield 'valid payload' => [
            [
                'csp-report' => [
                    'blocked-uri' => 'https://malicious-site.com',
                    'violated-directive' => 'script-src',
                    'document-uri' => 'https://histologe.fr',
                    'original-policy' => "default-src 'self'; script-src 'self'",
                    'source-file' => 'https://histologe.fr/js/app.js',
                    'line-number' => 42,
                    'column-number' => 10,
                ],
            ],
            true,
        ];

        yield 'payload with extension in source-file (chrome)' => [
            [
                'csp-report' => [
                    'blocked-uri' => 'https://malicious-site.com',
                    'violated-directive' => 'script-src',
                    'document-uri' => 'https://histologe.fr',
                    'source-file' => 'chrome-extension://',
                ],
            ],
            false,
        ];

        yield 'payload with extension in blocked-uri (xiaomi)' => [
            [
                'csp-report' => [
                    'blocked-uri' => 'https://cdn.alsgp0.fds.api.mi-img.com/instant-web/extension/test.min.js',
                    'violated-directive' => 'script-src',
                    'document-uri' => 'https://histologe.fr',
                    'source-file' => 'https://histologe.fr/js/app.js',
                ],
            ],
            false,
        ];

        yield 'payload with extension in source-file (safari)' => [
            [
                'csp-report' => [
                    'blocked-uri' => 'https://malicious-site.com',
                    'violated-directive' => 'script-src',
                    'document-uri' => 'https://histologe.fr',
                    'source-file' => 'safari-extension://',
                ],
            ],
            false,
        ];

        yield 'payload with extension in source-file (firefox)' => [
            [
                'csp-report' => [
                    'blocked-uri' => 'https://malicious-site.com',
                    'violated-directive' => 'script-src',
                    'document-uri' => 'https://histologe.fr',
                    'source-file' => 'moz-extension://',
                ],
            ],
            false,
        ];
    }

    public function testCspReportWithGetMethod(): void
    {
        $client = static::createClient();

        $client->request('GET', '/csp-report');

        // La route n'accepte que POST, donc GET devrait retourner 405 Method Not Allowed
        $this->assertEquals(Response::HTTP_METHOD_NOT_ALLOWED, $client->getResponse()->getStatusCode());
    }
}
