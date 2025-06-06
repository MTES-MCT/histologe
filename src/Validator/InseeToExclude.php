<?php

namespace App\Validator;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
#[\Attribute(\Attribute::TARGET_PROPERTY)]
class InseeToExclude extends Constraint
{
    public string $message = 'La valeur "{{ value }}" n\'est pas valide. Elle doit être une liste de codes INSEE séparés par des virgules ou vide.';

    public function getTargets(): string
    {
        return self::PROPERTY_CONSTRAINT;
    }
}
