<?php

namespace App\Service\Gouv\ProConnect;

use Lcobucci\JWT\Encoding\JoseEncoder;
use Lcobucci\JWT\Token\Parser;
use Lcobucci\JWT\Token\Plain;

class ProConnectJwtParser
{
    /**
     * @return array<string, mixed>
     */
    public function parse(string $jwt): array
    {
        /** @var non-empty-string $jwt */
        $jwt = trim($jwt);
        /** @var Plain $token */
        $token = (new Parser(new JoseEncoder()))->parse($jwt);

        return $token->claims()->all();
    }
}
