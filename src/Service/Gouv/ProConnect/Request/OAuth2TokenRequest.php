<?php

namespace App\Service\Gouv\ProConnect\Request;

class OAuth2TokenRequest
{
    public function __construct(
        public string $clientId,
        public string $clientSecret,
        public string $redirectUri,
        public string $code,
        public string $grantType = 'authorization_code',
    ) {
    }

    public function toArray(): array
    {
        return [
            'grant_type' => $this->grantType,
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'redirect_uri' => $this->redirectUri,
            'code' => $this->code,
        ];
    }

    public function toQueryString(): string
    {
        return http_build_query($this->toArray());
    }
}
