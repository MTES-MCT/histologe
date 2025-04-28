<?php

namespace App\Tests\Unit\Service\Gouv\ProConnect\Response;

use App\Service\Gouv\ProConnect\Response\JWKSResponse;
use PHPUnit\Framework\TestCase;

class JWKSResponseTest extends TestCase
{
    public function testDoNotFindPublicKey(): void
    {
        $data = __DIR__.'/../../../../../../tools/wiremock/src/Resources/ProConnect/jwks.json';
        $jwksResponse = json_decode(file_get_contents($data), true);
        $jwksResponse['keys'][0]['kty'] = 'invalid';
        $jwksResponse['keys'][0]['alg'] = 'invalid';

        $jwks = new JWKSResponse(json_encode($jwksResponse));

        $this->assertNull($jwks->findPublicKey());
    }
}
