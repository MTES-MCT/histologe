<?php

namespace App\Validator;

use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumberUtil;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class TelephoneFormatValidator extends ConstraintValidator
{
    public function __construct()
    {
    }

    public function validate(mixed $value, Constraint $constraint): void
    {
        /* @var App\Validator\TelephoneFormat $constraint */

        if (null === $value || '' === $value) {
            return;
        }

        try {
            $phoneNumberUtil = PhoneNumberUtil::getInstance();
            $phoneNumberParsed = $phoneNumberUtil->parse($value, 'FR');
            $isValid = $phoneNumberUtil->isValidNumber($phoneNumberParsed);
            if (!$isValid) {
                $this->context->buildViolation($constraint->message)
                    ->setParameter('{{ value }}', $value)
                    ->atPath('telephone')
                    ->addViolation();
            }
        } catch (NumberParseException $e) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ value }}', $value)
                ->atPath('telephone')
                ->addViolation();
        }
    }
}
