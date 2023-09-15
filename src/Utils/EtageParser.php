<?php

namespace App\Utils;

class EtageParser
{
    public static function parse(?string $etage): ?int
    {
        if (preg_match('/(rez|rdc|rdj|rh|chauss)/i', $etage, $matches)) {
            return 0;
        } elseif (preg_match('/\d+/', $etage, $matches)) {
            return (int) $matches[0];
        }

        return null;
    }
}
