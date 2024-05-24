<?php

namespace App\Validator;

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

        if ($errors->count() == 0) {
            return true;
        }
       
        return false;
    }
}