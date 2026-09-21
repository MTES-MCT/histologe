<?php

namespace App\Tests\Unit\Service\Gouv\ProConnect;

use App\Service\Gouv\ProConnect\ProConnectJwtValidator;
use App\Service\Gouv\ProConnect\Response\JWKSResponse;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\Clock\MockClock;

class ProConnectJwtValidatorTest extends TestCase
{
    private const EXPECTED_ISSUER = 'https://identite-sandbox.proconnect.gouv.fr';
    private const EXPECTED_AUDIENCE = 'client_id';

    public function testValidJwtReturnsTrue(): void
    {
        /** @var non-empty-string $jwt */
        $jwt = trim((string) file_get_contents(__DIR__.'/../../../../../tools/wiremock/src/Resources/ProConnect/userinfo.txt'));
        $jwksJson = (string) file_get_contents(__DIR__.'/../../../../../tools/wiremock/src/Resources/ProConnect/jwks.json');
        $jwks = new JWKSResponse((string) $jwksJson);

        $validator = new ProConnectJwtValidator(new NullLogger(), new MockClock('2025-04-10'));
        $result = $validator->validate($jwks, $jwt, 'fake_nonce', self::EXPECTED_ISSUER, self::EXPECTED_AUDIENCE);

        $this->assertTrue($result, 'JWT should be valid with correct public key and nonce');
    }

    public function testInvalidJwtReturnsFalse(): void
    {
        $invalidJwt = 'invalid.jwt.token';
        $jwksJson = (string) file_get_contents(__DIR__.'/../../../../../tools/wiremock/src/Resources/ProConnect/jwks.json');
        $jwks = new JWKSResponse((string) $jwksJson);

        $validator = new ProConnectJwtValidator(new NullLogger(), new MockClock('2025-04-10'));
        $result = $validator->validate($jwks, $invalidJwt, 'fake_nonce', self::EXPECTED_ISSUER, self::EXPECTED_AUDIENCE);

        $this->assertFalse($result, 'Invalid JWT should fail validation');
    }

    public function testInvalidNonceReturnsFalse(): void
    {
        /** @var non-empty-string $jwt */
        $jwt = trim((string) file_get_contents(__DIR__.'/../../../../../tools/wiremock/src/Resources/ProConnect/userinfo.txt'));
        $jwksJson = (string) file_get_contents(__DIR__.'/../../../../../tools/wiremock/src/Resources/ProConnect/jwks.json');
        $jwks = new JWKSResponse((string) $jwksJson);

        $validator = new ProConnectJwtValidator(new NullLogger(), new MockClock('2025-04-10'));
        $result = $validator->validate($jwks, $jwt, 'wrong_nonce', self::EXPECTED_ISSUER, self::EXPECTED_AUDIENCE);

        $this->assertFalse($result, 'JWT should fail if nonce does not match');
    }

    public function testWrongIssuerReturnsFalse(): void
    {
        /** @var non-empty-string $jwt */
        $jwt = trim((string) file_get_contents(__DIR__.'/../../../../../tools/wiremock/src/Resources/ProConnect/userinfo.txt'));
        $jwksJson = (string) file_get_contents(__DIR__.'/../../../../../tools/wiremock/src/Resources/ProConnect/jwks.json');
        $jwks = new JWKSResponse((string) $jwksJson);

        $validator = new ProConnectJwtValidator(new NullLogger(), new MockClock('2025-04-10'));
        $result = $validator->validate($jwks, $jwt, 'fake_nonce', 'https://not-proconnect.example.com', self::EXPECTED_AUDIENCE);

        $this->assertFalse($result, 'JWT should fail if issuer does not match');
    }

    public function testWrongAudienceReturnsFalse(): void
    {
        /** @var non-empty-string $jwt */
        $jwt = trim((string) file_get_contents(__DIR__.'/../../../../../tools/wiremock/src/Resources/ProConnect/userinfo.txt'));
        $jwksJson = (string) file_get_contents(__DIR__.'/../../../../../tools/wiremock/src/Resources/ProConnect/jwks.json');
        $jwks = new JWKSResponse((string) $jwksJson);

        $validator = new ProConnectJwtValidator(new NullLogger(), new MockClock('2025-04-10'));
        $result = $validator->validate($jwks, $jwt, 'fake_nonce', self::EXPECTED_ISSUER, 'wrong_client_id');

        $this->assertFalse($result, 'JWT should fail if audience does not match');
    }

    public function testMissingNonceIsIgnored(): void
    {
        /** @var non-empty-string $jwt */
        $jwt = trim((string) file_get_contents(__DIR__.'/../../../../../tools/wiremock/src/Resources/ProConnect/userinfo.txt'));
        $jwksJson = (string) file_get_contents(__DIR__.'/../../../../../tools/wiremock/src/Resources/ProConnect/jwks.json');
        $jwks = new JWKSResponse((string) $jwksJson);

        $validator = new ProConnectJwtValidator(new NullLogger(), new MockClock('2025-04-10'));
        $result = $validator->validate($jwks, $jwt, null, self::EXPECTED_ISSUER, self::EXPECTED_AUDIENCE);

        $this->assertTrue($result, 'JWT should be valid when no nonce is expected (userinfo flow)');
    }
}
