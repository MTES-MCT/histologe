<?php

namespace App\Tests\Functional\Controller\Webhook;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class CronReportingControllerTest extends WebTestCase
{
    private const string ENDPOINT = '/webhook/cron-report-mail';

    /**
     * @dataProvider provideCronPayloads
     */
    public function testHandleSendEmail(array $payload, int $expectedStatusCode, bool $useCorrectToken, int $expectedEmailCount): void
    {
        $client = static::createClient();
        $token = $useCorrectToken
            ? $client->getContainer()->getParameter('send_error_email_token')
            : 'wrong_token';

        $client->request(
            'POST',
            self::ENDPOINT,
            [],
            [],
            ['HTTP_AUTHORIZATION' => 'Bearer '.$token],
            (string) json_encode($payload)
        );

        $this->assertResponseStatusCodeSame($expectedStatusCode);
        if ($expectedEmailCount > 0) {
            $this->assertEmailCount($expectedEmailCount);
        }
    }

    public function provideCronPayloads(): \Generator
    {
        yield 'Success case' => [
            'payload' => [
                'title' => 'Test Job Success',
                'message' => 'Transfert S3 OK',
                'host' => 'server-01',
            ],
            'expectedStatusCode' => Response::HTTP_OK,
            'useCorrectToken' => true,
            'expectedEmailCount' => 1,
        ];

        yield 'Error report case' => [
            'payload' => [
                'title' => 'Test Job Error',
                'error' => 'Disk full',
                'database' => 'db_prod',
            ],
            'expectedStatusCode' => Response::HTTP_OK,
            'useCorrectToken' => true,
            'expectedEmailCount' => 1,
        ];

        yield 'Unauthorized case' => [
            'payload' => ['title' => 'Should fail'],
            'expectedStatusCode' => Response::HTTP_FORBIDDEN,
            'useCorrectToken' => false,
            'expectedEmailCount' => 0,
        ];

        yield 'Invalid payload case (missing title)' => [
            'payload' => ['message' => 'Missing title field'],
            'expectedStatusCode' => Response::HTTP_BAD_REQUEST,
            'useCorrectToken' => true,
            'expectedEmailCount' => 0,
        ];
    }
}
