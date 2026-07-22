<?php

namespace App\Utils;

class DateHelper
{
    public const string MIN_DATE = '1900-01-01';

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

    public static function formatValidDateInput(\DateTimeImmutable $dateInput, string $format): ?string
    {
        $minDate = new \DateTimeImmutable(self::MIN_DATE);
        $today = new \DateTimeImmutable();

        if ($dateInput < $minDate || $dateInput > $today) {
            return null;
        }

        return $dateInput->format($format);
    }

    /**
     * Traduit en français un seuil de durée au format attendu par DateTimeImmutable::modify()
     * (ex. '15 days' -> '15 jours'), sans changer la valeur numérique.
     */
    public static function translateDurationThresholdToFrench(string $threshold): string
    {
        [$value, $unit] = explode(' ', $threshold, 2);
        $units = [
            'day' => 'jour',
            'days' => 'jours',
            'week' => 'semaine',
            'weeks' => 'semaines',
            'month' => 'mois',
            'months' => 'mois',
            'year' => 'an',
            'years' => 'ans',
        ];

        return $value.' '.($units[$unit] ?? $unit);
    }
}
