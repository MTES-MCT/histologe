<?php

namespace App\Validator;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
#[\Attribute(\Attribute::TARGET_PROPERTY)]
class PartnerToExclude extends Constraint
{
    public string $message = 'La valeur "{{ value }}" n\'est pas valide. Elle doit être une liste d\'Id partenaires séparés par des virgules ou vide.';

    public function getTargets(): string
    {
        return self::PROPERTY_CONSTRAINT;
    }
}
