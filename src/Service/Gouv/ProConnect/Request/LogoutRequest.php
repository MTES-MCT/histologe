<?php

namespace App\Service\Gouv\ProConnect\Request;

namespace App\Service\Gouv\ProConnect\Request;

use App\Exception\ProConnect\ProConnectException;

readonly class LogoutRequest
{
    /**
     * @throws ProConnectException
     */
    public function __construct(
        public ?string $idTokenHint,
        public ?string $state,
        public ?string $postLogoutRedirectUri,
    ) {
        if (!$this->idTokenHint || !$state || !$postLogoutRedirectUri) {
            throw new ProConnectException('Paramètres token, state ou postLogoutRedirectUri manquants.');
        }
    }

    public function toQueryString(): string
    {
        $params = [
            'id_token_hint' => $this->idTokenHint,
            'state' => $this->state,
            'post_logout_redirect_uri' => $this->postLogoutRedirectUri,
        ];

        return http_build_query($params);
    }
}
