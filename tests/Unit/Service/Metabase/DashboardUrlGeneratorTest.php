<?php

namespace App\Tests\Unit\Service\Metabase;

use App\Service\Metabase\DashboardKey;
use App\Service\Metabase\DashboardUrlGenerator;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Token;
use Lcobucci\JWT\UnencryptedToken;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Random\RandomException;
use Symfony\Component\Clock\MockClock;

class DashboardUrlGeneratorTest extends TestCase
{
    /**
     * @throws \DateMalformedStringException
     * @throws \DateInvalidTimeZoneException
     * @throws RandomException
     */
    public function testGenerateBuildsExpectedUrlAndToken(): void
    {
        $siteUrl = 'https://metabase.example.test';
        $secretKey = bin2hex(random_bytes(32));
        $ttlInMinutes = '10';

        $clock = new MockClock(new \DateTimeImmutable('2025-01-01T10:00:00+00:00'));
        $logger = $this->createMock(LoggerInterface::class);

        $dashboardUrlGenerator = new DashboardUrlGenerator(
            $siteUrl,
            $secretKey,
            $ttlInMinutes,
            $clock,
            $logger
        );

        $params = ['territory' => '76'];
        $queryParams = ['foo' => 'bar', 'baz' => 'qux'];

        $dashboardKey = DashboardKey::DASHBOARD_BO;
        $url = $dashboardUrlGenerator->generate($dashboardKey, $params, $queryParams);
        $this->assertNotNull($url, 'URL should not be null');

        $this->assertStringStartsWith($siteUrl.'/embed/dashboard/', $url);
        $this->assertStringContainsString('#bordered=false&titled=false&theme=transparent', $url);
        $this->assertStringContainsString('?foo=bar&baz=qux', $url);

        $path = parse_url($url, \PHP_URL_PATH);
        $this->assertIsString($path);

        $segments = explode('/', trim($path, '/'));
        $this->assertNotEmpty($segments);
        $tokenString = end($segments);
        $this->assertNotEmpty($tokenString, 'Token should be present at the end of the path');

        // Parser le token JWT avec la même configuration
        $jwtConfig = Configuration::forSymmetricSigner(
            new Sha256(),
            InMemory::plainText($secretKey)
        );

        $token = $jwtConfig->parser()->parse($tokenString);

        /** @var UnencryptedToken $token */
        $claims = $token->claims();

        $this->assertSame(
            ['dashboard' => $dashboardKey->value],
            $claims->get('resource'),
            'resource.dashboard claim should match dashboard key'
        );

        $this->assertSame(
            $params,
            $claims->get('params'),
            'params claim should match the provided params'
        );

        $exp = $claims->get('exp');
        $this->assertInstanceOf(\DateTimeImmutable::class, $exp);

        $this->assertSame(
            '2025-01-01T10:10:00+00:00',
            $exp->format(\DATE_ATOM),
            'Expiration should be 10 minutes after the clock time'
        );

        $this->assertEquals((10 - 1) * 60, $dashboardUrlGenerator->getTtlInSeconds(), 'TTL should be 9 minutes = 540 seconds');
    }
}
