<?php

namespace App\Service\Gouv\ProConnect\Response;

use App\Exception\ProConnect\ProConnectException;

class OAuth2TokenResponse
{
    public ?string $accessToken = null;
    public ?int $expiresIn = null;
    public ?string $idToken = null;
    public ?string $scope = null;
    public ?string $tokenType = null;

    public function __construct(array $data)
    {
        $this->accessToken = $data['access_token'] ?? null;
        $this->expiresIn = $data['expires_in'] ?? null;
        $this->idToken = $data['id_token'] ?? null;
        $this->scope = $data['scope'] ?? null;
        $this->tokenType = $data['token_type'] ?? null;

        if (empty($this->accessToken) || empty($this->idToken)) {
            throw new ProConnectException('Erreur lors de la récupération des tokens');
        }
    }
}
