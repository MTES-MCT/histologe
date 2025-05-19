<?php

namespace App\Utils;

class TrimHelper
{
    public static function safeTrim(mixed $value): mixed
    {
        return is_string($value) ? mb_trim($value) : $value;
    }
}
