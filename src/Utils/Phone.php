<?php

namespace App\Utils;

use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberUtil;

class Phone
{
    public static function format(?string $tel, bool $national = false): ?string
    {
        if (!$tel) {
            return $tel;
        }
        try {
            $telDecoded = json_decode($tel);
            $phoneNumberUtil = PhoneNumberUtil::getInstance();
            if ($telDecoded && isset($telDecoded->phone_number) && isset($telDecoded->country_code)) {
                $phoneNumberParsed = $phoneNumberUtil->parse($telDecoded->phone_number, substr($telDecoded->country_code, 0, 2));
            } else {
                $phoneNumberParsed = $phoneNumberUtil->parse($tel, 'FR');
            }
            if ($national) {
                return str_replace(' ', '', $phoneNumberUtil->format($phoneNumberParsed, PhoneNumberFormat::NATIONAL));
            }

            return $phoneNumberUtil->format($phoneNumberParsed, PhoneNumberFormat::E164);
        } catch (\Exception $e) {
            return $tel;
        }
    }
}
