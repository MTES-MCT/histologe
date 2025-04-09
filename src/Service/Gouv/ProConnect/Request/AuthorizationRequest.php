<?php

namespace App\Service\Gouv\ProConnect\Request;

class AuthorizationRequest
{
    public function __construct(
        public string $clientId,
        public string $redirectUri,
        public string $state,
        public string $nonce,
        public string $responseType = 'code',
        public string $acrValues = 'eidas1',
        public string $scope = 'openid given_name usual_name email uid',
    ) {
    }

    public function toQueryString(): string
    {
        $params = [
            'response_type' => $this->responseType,
            'client_id' => $this->clientId,
            'redirect_uri' => $this->redirectUri,
            'acr_values' => $this->acrValues,
            'scope' => $this->scope,
            'state' => $this->state,
            'nonce' => $this->nonce,
        ];

        return http_build_query($params);
    }
}
