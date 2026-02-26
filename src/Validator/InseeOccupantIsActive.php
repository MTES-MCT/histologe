<?php

namespace App\Validator;

use Symfony\Component\Validator\Constraint;

#[\Attribute]
class InseeOccupantIsActive extends Constraint
{
    public string $messageInsee = 'Le territoire n\'est pas actif pour le code INSEE "{{ code }}".';
    public string $messagePostalCode = 'Le territoire n\'est pas actif pour le code postal "{{ code }}".';

    public function getTargets(): string
    {
        return self::CLASS_CONSTRAINT;
    }
}
