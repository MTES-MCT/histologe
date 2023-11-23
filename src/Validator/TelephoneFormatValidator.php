<?php

namespace App\Validator;

use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumberUtil;
use Symfony\Component\Form\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class TelephoneFormatValidator extends ConstraintValidator
{
    public function __construct()
    {
    }

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof TelephoneFormat) {
            throw new UnexpectedTypeException($constraint, __NAMESPACE__.'\TelephoneFormat');
        }

        /* @var TelephoneFormat $constraint */

        if (null === $value || '' === $value) {
            return;
        }

        $ext = 'FR'; // will be abandonned soon
        if (\is_array($value)) {
            $value = $value['phone_number'];
            if (!empty($value['country_code']) && str_contains($value['country_code'], ':')) {
                $ext = explode(':', $value['country_code'])[0];
            }
        }

        try {
            $phoneNumberUtil = PhoneNumberUtil::getInstance();
            $phoneNumberParsed = $phoneNumberUtil->parse($value, $ext);
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
