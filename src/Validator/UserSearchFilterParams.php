<?php

namespace App\Validator;

use Symfony\Component\Validator\Constraint;

#[\Attribute]
class UserSearchFilterParams extends Constraint
{
    public string $message = 'Vous avez déjà une recherche avec les mêmes filtres.';

    public function getTargets(): string
    {
        return self::CLASS_CONSTRAINT;
    }
}
