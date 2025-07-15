<?php

namespace App\Validator;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
class ChildrenLessThanPeople extends Constraint
{
    public string $message = 'Le nombre d\'enfants ({{ children }}) doit être inférieur ou égal au nombre total de personnes ({{ people }}).';

    public function getTargets(): string
    {
        return self::CLASS_CONSTRAINT;
    }
}
