<?php

namespace App\Validator;

use Egulias\EmailValidator\EmailValidator;
use Egulias\EmailValidator\Validation\DNSCheckValidation;
use Egulias\EmailValidator\Validation\MultipleValidationWithAnd;
use Egulias\EmailValidator\Validation\RFCValidation;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Validation;

class EmailFormatValidator
{
    public static function validate(mixed $value): bool
    {
        if (null === $value || '' === $value) {
            return false;
        }

        $emailConstraint = new Assert\Email(mode: Email::VALIDATION_MODE_STRICT);
        $validator = Validation::createValidator();
        $errors = $validator->validate(
            $value,
            $emailConstraint
        );

        $eguliasValidator = new EmailValidator();
        $multipleValidations = new MultipleValidationWithAnd([
                new RFCValidation(),
                new DNSCheckValidation(),
        ]);
        $emailValid = $eguliasValidator->isValid($value, $multipleValidations);
        if (0 == $errors->count() && $emailValid) {
            return true;
        }

        return false;
    }
}
