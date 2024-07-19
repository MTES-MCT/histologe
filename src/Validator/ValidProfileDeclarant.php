<?php

namespace App\Validator;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
#[\Attribute(\Attribute::TARGET_PROPERTY)]
class ValidProfileDeclarant extends Constraint
{
    public string $message = 'La valeur "{{ value }}" n\'est pas un profil déclarant ou groupe de profils valide.';

    public function getTargets(): string
    {
        return self::PROPERTY_CONSTRAINT;
    }
}
