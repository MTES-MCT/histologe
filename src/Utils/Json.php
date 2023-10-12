<?php

namespace App\Utils;

class Json
{
    public static function encode(mixed $value): null|string|false
    {
        if (null === $value) {
            return null;
        }

        return json_encode($value);
    }
}
