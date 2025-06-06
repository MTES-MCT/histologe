<?php

namespace App\Utils;

class DateHelper
{
    public static function isValidDate(?string $date, string $format = 'Y-m-d H:i:s'): bool
    {
        $datetime = \DateTimeImmutable::createFromFormat($format, $date);

        return $datetime && $datetime->format($format) === $date;
    }
}
