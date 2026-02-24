<?php

namespace App\Validator;

use Symfony\Component\Validator\Constraint;

#[\Attribute]
class InseeOccupantIsActive extends Constraint
{
    public string $message = 'Le territoire n\'est pas actif pour le code INSEE "{{ inseeCode }}".';

    public function getTargets(): string
    {
        return self::CLASS_CONSTRAINT;
    }
}
