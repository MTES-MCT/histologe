<?php

namespace App\Utils;

class Json
{
    public static function encode(mixed $value): string|false|null
    {
        if (null === $value) {
            return null;
        }

        return json_encode($value);
    }
}
