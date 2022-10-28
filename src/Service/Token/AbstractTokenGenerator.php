<?php

namespace App\Service\Token;

abstract class AbstractTokenGenerator implements TokenGeneratorInterface
{
    public const LENGTH = 32;

    public function generateToken(): string
    {
        return bin2hex(openssl_random_pseudo_bytes(self::LENGTH));
    }
}
