<?php

namespace App\Tests\Unit\Service\Gouv\ProConnect;

use App\Service\Gouv\ProConnect\ProConnectJwtValidator;
use App\Service\Gouv\ProConnect\Response\JWKSResponse;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

class ProConnectJwtValidatorTest extends TestCase
{
    public function testValidJwtReturnsTrue(): void
    {
        $jwt = trim((string) file_get_contents(__DIR__.'/../../../../../tools/wiremock/src/Resources/ProConnect/userinfo.txt'));
        $jwksJson = (string) file_get_contents(__DIR__.'/../../../../../tools/wiremock/src/Resources/ProConnect/jwks.json');
        $jwks = new JWKSResponse((string) $jwksJson);

        $validator = new ProConnectJwtValidator(new NullLogger());
        $result = $validator->validate($jwks, $jwt, 'fake_nonce');

        $this->assertTrue($result, 'JWT should be valid with correct public key and nonce');
    }

    public function testInvalidJwtReturnsFalse(): void
    {
        $invalidJwt = 'invalid.jwt.token';
        $jwksJson = (string) file_get_contents(__DIR__.'/../../../../../tools/wiremock/src/Resources/ProConnect/jwks.json');
        $jwks = new JWKSResponse((string) $jwksJson);

        $validator = new ProConnectJwtValidator(new NullLogger());
        $result = $validator->validate($jwks, $invalidJwt, 'fake_nonce');

        $this->assertFalse($result, 'Invalid JWT should fail validation');
    }

    public function testInvalidNonceReturnsFalse(): void
    {
        $jwt = trim((string) file_get_contents(__DIR__.'/../../../../../tools/wiremock/src/Resources/ProConnect/userinfo.txt'));
        $jwksJson = (string) file_get_contents(__DIR__.'/../../../../../tools/wiremock/src/Resources/ProConnect/jwks.json');
        $jwks = new JWKSResponse((string) $jwksJson);

        $validator = new ProConnectJwtValidator(new NullLogger());
        $result = $validator->validate($jwks, $jwt, 'wrong_nonce');

        $this->assertFalse($result, 'JWT should fail if nonce does not match');
    }
}
