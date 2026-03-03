<?php

namespace App\Tests\Unit\Service\Mailer\Error;

use App\Service\Mailer\Mail\Error\ErrorSignalementMailer;
use PHPUnit\Framework\TestCase;

class ErrorSignalementMailerTest extends TestCase
{
    /**
     * @throws \ReflectionException
     */
    public function testSanitizeFoundContent(): void
    {
        $mailer = $this->createMock(ErrorSignalementMailer::class);

        $reflection = new \ReflectionClass($mailer);
        $method = $reflection->getMethod('sanitizeContent');

        $rawPayload = json_encode([
            'password' => 'secret',
            'password-current' => 'secret',
            'password-repeat' => 'secret',
            '_token' => 'secret',
        ]);

        $result = json_decode($method->invoke($mailer, $rawPayload), true);

        $countFiltered = array_count_values($result)['[Filtered]'] ?? 0;
        $countSecret = array_count_values($result)['secret'] ?? 0;
        $this->assertEquals(0, $countSecret);
        $this->assertEquals(4, $countFiltered);
    }

    /**
     * @throws \ReflectionException
     */
    public function testSanitizeNotFoundContent(): void
    {
        $mailer = $this->createMock(ErrorSignalementMailer::class);

        $reflection = new \ReflectionClass($mailer);
        $method = $reflection->getMethod('sanitizeContent');

        $rawPayload = 'Hello world';

        $result = $method->invoke($mailer, $rawPayload);

        $this->assertStringContainsString('Hello world', $result);
    }
}
