<?php

namespace App\Service\Token;

interface TokenGeneratorInterface
{
    public function generateToken(): string;
}
