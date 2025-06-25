<?php

namespace App\Tests\Functional\Controller\Webhook;

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

    public function testHandleWithValidIpAndValidEvent(): void
    {
        $client = static::createClient();

        $client->request('POST', '/webhook/brevo', [], [], [
            'REMOTE_ADDR' => '127.0.0.1',
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'event' => 'delivered',
            'email' => 'test@example.com',
        ]));

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $this->assertEquals('OK', $client->getResponse()->getContent());
    }

    public function testHandleWithInvalidIp(): void
    {
        $client = static::createClient();

        $client->request('POST', '/webhook/brevo', [], [], [
            'REMOTE_ADDR' => '192.168.1.100',
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'event' => 'delivered',
            'email' => 'test@example.com',
        ]));

        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
        $this->assertEquals('Forbidden', $client->getResponse()->getContent());
    }

    public function testHandleWithMissingEvent(): void
    {
        $client = static::createClient();

        $client->request('POST', '/webhook/brevo', [], [], [
            'REMOTE_ADDR' => '127.0.0.1',
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'email' => 'test@example.com',
        ]));

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        $this->assertEquals('Bad Request', $client->getResponse()->getContent());
    }

    public function testHandleWithBlockedEvent(): void
    {
        $client = static::createClient();

        $client->request('POST', '/webhook/brevo', [], [], [
            'REMOTE_ADDR' => '127.0.0.1',
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'event' => 'blocked',
            'email' => 'test@example.com',
        ]));

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $this->assertEquals('OK', $client->getResponse()->getContent());
    }

    public function testHandleWithHardBounceEvent(): void
    {
        $client = static::createClient();

        $client->request('POST', '/webhook/brevo', [], [], [
            'REMOTE_ADDR' => '127.0.0.1',
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'event' => 'hard_bounce',
            'email' => 'test@example.com',
        ]));

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $this->assertEquals('OK', $client->getResponse()->getContent());
    }

    public function testHandleWithInvalidJson(): void
    {
        $client = static::createClient();

        $client->request('POST', '/webhook/brevo', [], [], [
            'REMOTE_ADDR' => '127.0.0.1',
            'CONTENT_TYPE' => 'application/json',
        ], 'invalid json');

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        $this->assertEquals('Bad Request', $client->getResponse()->getContent());
    }
}
