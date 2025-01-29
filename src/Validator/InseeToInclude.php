<?php

namespace App\Validator;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
#[\Attribute(\Attribute::TARGET_PROPERTY)]
class InseeToInclude extends Constraint
{
    public $message = 'La valeur "{{ value }}" n\'est pas valide. Elle doit être soit vide soit une liste de codes INSEE séparés par des virgules.';

    public function getTargets(): string
    {
        return self::PROPERTY_CONSTRAINT;
    }
}
