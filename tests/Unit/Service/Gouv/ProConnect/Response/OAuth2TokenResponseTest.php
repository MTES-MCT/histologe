<?php

namespace App\Tests\Unit\Service\Gouv\ProConnect\Response;

use App\Exception\ProConnect\ProConnectException;
use App\Service\Gouv\ProConnect\Response\OAuth2TokenResponse;
use PHPUnit\Framework\TestCase;

class OAuth2TokenResponseTest extends TestCase
{
    public function testConstructThrowsExceptionWhenAccessTokenIsMissing(): void
    {
        $this->expectException(ProConnectException::class);
        $this->expectExceptionMessage('Erreur lors de la récupération des tokens');

        $response = new OAuth2TokenResponse([
            'id_token' => 'id-token-456',
            'expires_in' => 3600,
        ]);

        $idToken = $response->idToken;
        $this->assertNotNull($idToken);
    }

    public function testConstructThrowsExceptionWhenIdTokenIsMissing(): void
    {
        $this->expectException(ProConnectException::class);
        $this->expectExceptionMessage('Erreur lors de la récupération des tokens');

        $response = new OAuth2TokenResponse([
            'access_token' => 'access-token-123',
            'expires_in' => 3600,
        ]);

        $accessToken = $response->accessToken;
        $this->assertNotNull($accessToken);
    }
}
