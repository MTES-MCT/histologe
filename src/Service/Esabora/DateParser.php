<?php

namespace App\Service\Esabora;

class DateParser
{
    public static function parse(string $date): \DateTimeImmutable
    {
        if (false !== $dateParsed = \DateTimeImmutable::createFromFormat(
            AbstractEsaboraService::FORMAT_DATE_TIME,
            $date)
        ) {
            return $dateParsed;
        }

        return \DateTimeImmutable::createFromFormat(AbstractEsaboraService::FORMAT_DATE, $date);
    }
}
