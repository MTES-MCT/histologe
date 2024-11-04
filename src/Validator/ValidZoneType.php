<?php

namespace App\Validator;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
#[\Attribute(\Attribute::TARGET_PROPERTY)]
class ValidZoneType extends Constraint
{
    public string $message = 'La valeur "{{ value }}" n\'est pas un ZoneType valide.';

    public function getTargets(): string
    {
        return self::PROPERTY_CONSTRAINT;
    }
}
