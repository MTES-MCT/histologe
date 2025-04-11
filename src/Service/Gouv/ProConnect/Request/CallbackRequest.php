<?php

namespace App\Service\Gouv\ProConnect\Request;

use App\Exception\ProConnect\ProConnectException;

class CallbackRequest
{
    /**
     * @throws ProConnectException
     */
    public function __construct(
        public string $code,
        public string $state,
    ) {
        if (!$code || !$state) {
            throw new ProConnectException('Paramètres code ou state manquants.');
        }
    }

    /**
     * @throws ProConnectException
     */
    public static function fromArray(array $params): self
    {
        return new self(
            code: $params['code'] ?? '',
            state: $params['state'] ?? ''
        );
    }
}
