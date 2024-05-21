<?php

namespace App\Service;

use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class EmailValidator
{
    public static function validate(
        ValidatorInterface $validator,
        string $emailAddress
    ): bool {
        $emailConstraint = new Assert\Email();
        $errors = $validator->validate(
            $emailAddress,
            $emailConstraint
        );

        return 0 == $errors->count();
    }
}
