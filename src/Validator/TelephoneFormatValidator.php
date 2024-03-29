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

        try {
            $phoneNumberUtil = PhoneNumberUtil::getInstance();
            $phoneNumberParsed = $phoneNumberUtil->parse($value, 'FR');
            $isPossible = $phoneNumberUtil->isPossibleNumber($phoneNumberParsed);
            if (!$isPossible) {
                $this->context->buildViolation($constraint->message)
                    ->setParameter('{{ value }}', $value)
                    ->addViolation();
            }
        } catch (NumberParseException $e) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ value }}', $value)
                ->addViolation();
        }
    }
}
