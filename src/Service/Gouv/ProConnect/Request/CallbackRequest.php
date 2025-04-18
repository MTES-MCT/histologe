<?php

namespace App\Service\Gouv\ProConnect\Request;

use App\Exception\ProConnect\ProConnectException;

class CallbackRequest
{
    /**
     * @throws ProConnectException
     */
    public function __construct(
        public ?string $code = null,
        public ?string $state = null,
    ) {
        if (!$code || !$state) {
            throw new ProConnectException('Paramètres code ou state manquants.');
        }
    }
}
