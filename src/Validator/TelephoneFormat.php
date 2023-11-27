<?php

namespace App\Validator;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
#[\Attribute(\Attribute::TARGET_PROPERTY)]
class TelephoneFormat extends Constraint
{
    /*
     * Any public properties become valid options for the annotation.
     * Then, use these in your validator class.
     */
    public string $message = 'Le numéro de téléphone "{{ value }}" n\'est pas au bon format.';

    public function getTargets(): string
    {
        return self::PROPERTY_CONSTRAINT;
    }
}
