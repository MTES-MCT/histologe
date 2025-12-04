<?php

namespace App\Service\InjonctionBailleur;

class BailleurLoginCodeGenerator
{
    public const LOGIN_CODE_LENGTH = 16;
    public const KEYSPACE = '23456789ABCDEFGHJKLMNPQRSTUVQXYZ';

    public static function generate(): string
    {
        $loginCode = '';
        $max = strlen(self::KEYSPACE) - 1;
        for ($i = 1; $i <= self::LOGIN_CODE_LENGTH; ++$i) {
            $loginCode .= self::KEYSPACE[random_int(0, $max)];
        }
        $loginCode = implode('-', str_split($loginCode, 4));

        return $loginCode;
    }
}
