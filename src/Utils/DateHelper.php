<?php

namespace App\Utils;

class DateHelper
{
    public static function isValidDate(?string $date, string $format = 'Y-m-d H:i:s'): bool
    {
        $datetime = \DateTimeImmutable::createFromFormat($format, $date);

        return $datetime && $datetime->format($format) === $date;
    }

    public static function formatDateString(string $value, string $fromFormat = 'Y-m-d', string $toFormat = 'd/m/Y'): string|false
    {
        // détecte yyyy-mm-dd strict
        if ('Y-m-d' === $fromFormat && preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            $date = \DateTimeImmutable::createFromFormat($fromFormat, $value);
            $errors = \DateTimeImmutable::getLastErrors();

            if ($date && !$errors) {
                return $date->format($toFormat);
            }
        }

        return false;
    }
}
