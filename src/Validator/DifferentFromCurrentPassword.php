<?php

namespace App\Validator;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
#[\Attribute(\Attribute::TARGET_PROPERTY)]
class DifferentFromCurrentPassword extends Constraint
{
    public string $message = 'Votre nouveau mot de passe doit être différent de votre mot de passe actuel.';

    public function getTargets(): string
    {
        return self::PROPERTY_CONSTRAINT;
    }
}
