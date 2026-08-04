<?php

namespace App\Utils;

class StringHelper
{
    public static function normalize(string $str): string
    {
        $normalized = strtolower((string) iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $str));
        $normalized = str_replace('-', ' ', $normalized);
        $normalized = str_replace("'", ' ', $normalized);

        return trim((string) preg_replace('/\s+/', ' ', $normalized));
    }
}
