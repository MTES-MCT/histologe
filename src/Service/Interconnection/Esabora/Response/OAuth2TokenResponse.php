<?php

namespace App\Service\Interconnection\Esabora\Response;

use App\Service\Interconnection\Esabora\Exception\EsaboraTokenException;

class OAuth2TokenResponse
{
    public ?string $accessToken = null;
    public ?int $expiresIn = null;
    public ?string $tokenType = null;

    /**
     * @param array<string, mixed> $data
     */
    public function __construct(array $data)
    {
        $this->accessToken = $data['access_token'] ?? null;
        $this->expiresIn = $data['expires_in'] ?? null;
        $this->tokenType = $data['token_type'] ?? null;

        if (empty($data['access_token']) || !\is_string($data['access_token'])) {
            throw new EsaboraTokenException('L\'access_token est absent de la réponse OAuth2.');
        }
    }
}
