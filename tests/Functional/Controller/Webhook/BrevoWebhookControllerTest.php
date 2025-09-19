<?php

namespace App\Tests\Functional\Controller\Webhook;

use App\Repository\EmailDeliveryIssueRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class BrevoWebhookControllerTest extends WebTestCase
{
    private ?string $originalBrevoAllowedIps = null;

    protected function setUp(): void
    {
        $this->originalBrevoAllowedIps = getenv('BREVO_ALLOWED_IPS') ?: null;

        putenv('BREVO_ALLOWED_IPS=127.0.0.1/32');
        $_ENV['BREVO_ALLOWED_IPS'] = '127.0.0.1/32';
        $_SERVER['BREVO_ALLOWED_IPS'] = '127.0.0.1/32';

        self::ensureKernelShutdown();
    }

    protected function tearDown(): void
    {
        if (null !== $this->originalBrevoAllowedIps) {
            putenv('BREVO_ALLOWED_IPS='.$this->originalBrevoAllowedIps);
            $_ENV['BREVO_ALLOWED_IPS'] = $this->originalBrevoAllowedIps;
            $_SERVER['BREVO_ALLOWED_IPS'] = $this->originalBrevoAllowedIps;
        } else {
            putenv('BREVO_ALLOWED_IPS');
            unset($_ENV['BREVO_ALLOWED_IPS']);
            unset($_SERVER['BREVO_ALLOWED_IPS']);
        }

        parent::tearDown();
    }

    /**
     * @dataProvider provideWebhookTestData
     */
    public function testWebhookHandling(
        string $remoteAddr,
        string $payload,
        int $expectedStatusCode,
        string $expectedContent,
    ): void {
        $client = static::createClient();

        $client->request(
            'POST',
            '/webhook/brevo',
            [],
            [],
            [
                'REMOTE_ADDR' => $remoteAddr,
                'CONTENT_TYPE' => 'application/json',
            ],
            $payload
        );

        $this->assertResponseStatusCodeSame($expectedStatusCode);
        $this->assertEquals($expectedContent, $client->getResponse()->getContent());
    }

    public function provideWebhookTestData(): \Generator
    {
        yield 'Valid IP and valid event' => [
            'remoteAddr' => '127.0.0.1',
            'payload' => json_encode([
                'event' => 'delivered',
                'email' => 'test@example.com',
            ]),
            'expectedStatusCode' => Response::HTTP_OK,
            'expectedContent' => 'OK',
        ];

        yield 'Invalid IP' => [
            'remoteAddr' => '192.168.1.100',
            'payload' => json_encode([
                'event' => 'delivered',
                'email' => 'test@example.com',
            ]),
            'expectedStatusCode' => Response::HTTP_FORBIDDEN,
            'expectedContent' => 'Forbidden',
        ];

        yield 'Missing event' => [
            'remoteAddr' => '127.0.0.1',
            'payload' => json_encode([
                'email' => 'test@example.com',
            ]),
            'expectedStatusCode' => Response::HTTP_BAD_REQUEST,
            'expectedContent' => 'Bad Request',
        ];

        yield 'Blocked event' => [
            'remoteAddr' => '127.0.0.1',
            'payload' => json_encode([
                'event' => 'blocked',
                'email' => 'test@example.com',
            ]),
            'expectedStatusCode' => Response::HTTP_OK,
            'expectedContent' => 'OK',
        ];

        yield 'Hard bounce event' => [
            'remoteAddr' => '127.0.0.1',
            'payload' => json_encode([
                'event' => 'hard_bounce',
                'email' => 'test@example.com',
            ]),
            'expectedStatusCode' => Response::HTTP_OK,
            'expectedContent' => 'OK',
        ];

        yield 'Invalid JSON' => [
            'remoteAddr' => '127.0.0.1',
            'payload' => 'invalid json',
            'expectedStatusCode' => Response::HTTP_BAD_REQUEST,
            'expectedContent' => 'Bad Request',
        ];
    }

    /**
     * @dataProvider provideWebhookEventTestData
     *
     * @param array<string, mixed>|null $expectedPayload
     */
    public function testHandleWebhookWithEvent(string $event, string $email, bool $expectDeliveryIssue, ?array $expectedPayload): void
    {
        $client = static::createClient();
        $container = static::getContainer();

        $client->request(
            'POST',
            '/webhook/brevo',
            [],
            [],
            [
                'REMOTE_ADDR' => '127.0.0.1',
                'CONTENT_TYPE' => 'application/json',
            ],
            json_encode([
                'event' => $event,
                'email' => $email,
            ])
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $this->assertEquals('OK', $client->getResponse()->getContent());

        $emailDeliveryIssue = $container->get(EmailDeliveryIssueRepository::class)->findOneBy(['email' => $email]);

        if ($expectDeliveryIssue) {
            $this->assertNotNull($emailDeliveryIssue);
            $this->assertEquals($email, $emailDeliveryIssue->getEmail());
            $this->assertEquals($event, $emailDeliveryIssue->getEvent()->value);
            $this->assertEquals(json_encode($expectedPayload), json_encode($emailDeliveryIssue->getPayload()));
        } else {
            $this->assertNull($emailDeliveryIssue);
        }
    }

    public function provideWebhookEventTestData(): \Generator
    {
        yield 'Soft bounce event' => [
            'event' => 'soft_bounce',
            'email' => 'baptiste@yopmail.com',
            'expectDeliveryIssue' => true,
            'expectedPayload' => [
                'event' => 'soft_bounce',
                'email' => 'baptiste@yopmail.com',
            ],
        ];

        yield 'Delivery event' => [
            'event' => 'delivered',
            'email' => 'nawell.mapaire@yopmail.com',
            'expectDeliveryIssue' => false,
            'expectedPayload' => null,
        ];
    }
}
