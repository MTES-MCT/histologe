<?php

namespace App\Validator;

use Symfony\Component\Validator\Constraint;

#[\Attribute]
class NatureLogementAutreRequired extends Constraint
{
    public string $message = 'Veuillez préciser la nature du logement.';

    public function getTargets(): string
    {
        return self::CLASS_CONSTRAINT;
    }
}
