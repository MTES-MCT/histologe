<?php

namespace App\Service\Interconnection\Esabora;

class DateParser
{
    /**
     * @throws \Exception
     */
    public static function parse(string $date, string $timezone = 'UTC'): \DateTimeImmutable
    {
        $fromTimezone = new \DateTimeZone($timezone);
        $toTimezone = new \DateTimeZone('UTC');
        if (false !== $dateLocaleParsed = \DateTimeImmutable::createFromFormat(
            AbstractEsaboraService::FORMAT_DATE_TIME,
            $date,
            $fromTimezone)
        ) {
            return $dateLocaleParsed->setTimezone($toTimezone);
        }
        $dateParsed = \DateTimeImmutable::createFromFormat(AbstractEsaboraService::FORMAT_DATE, $date);
        if (false === $dateParsed) {
            throw new \InvalidArgumentException(sprintf('Date invalide "%s" pour les formats %s ou %s', $date, AbstractEsaboraService::FORMAT_DATE_TIME, AbstractEsaboraService::FORMAT_DATE));
        }

        return $dateParsed->setTime(0, 0);
    }
}
