<?php

namespace App\Service\Gouv\ProConnect\Response;

class DiscoveryEndpointsResponse
{
    public ?string $authorizationEndpoint = null;
    public ?string $tokenEndpoint = null;
    public ?string $userInfoEndpoint = null;
    public ?string $endSessionEndpoint = null;
    public ?string $jwksUri = null;

    public function __construct(array $data)
    {
        $this->authorizationEndpoint = $data['authorization_endpoint'] ?? null;
        $this->tokenEndpoint = $data['token_endpoint'] ?? null;
        $this->userInfoEndpoint = $data['userinfo_endpoint'] ?? null;
        $this->endSessionEndpoint = $data['end_session_endpoint'] ?? null;
        $this->jwksUri = $data['jwks_uri'] ?? null;
    }
}
