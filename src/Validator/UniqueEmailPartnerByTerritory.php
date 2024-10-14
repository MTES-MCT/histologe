<?php

namespace App\Validator;

use Symfony\Component\Validator\Constraint;

#[\Attribute(\Attribute::TARGET_CLASS)]
class UniqueEmailPartnerByTerritory extends Constraint
{
    public string $message = 'L\'e-mail partenaire "{{ email }}" existe déjà pour le territoire "{{ territory }}"';

    public function getTargets(): string
    {
        return self::CLASS_CONSTRAINT;
    }
}
