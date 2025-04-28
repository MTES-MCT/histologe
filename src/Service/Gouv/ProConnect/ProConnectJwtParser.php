<?php

namespace App\Service\Gouv\ProConnect;

use Lcobucci\JWT\Encoding\JoseEncoder;
use Lcobucci\JWT\Token\Parser;
use Lcobucci\JWT\Token\Plain;

class ProConnectJwtParser
{
    public function parse(string $jwt): array
    {
        /** @var Plain $token */
        $token = (new Parser(new JoseEncoder()))->parse(trim($jwt));

        return $token->claims()->all();
    }
}
