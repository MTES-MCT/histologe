<?php

namespace App\Validator;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
#[\Attribute(\Attribute::TARGET_PROPERTY)]
class ZoneIds extends Constraint
{
    public string $message = 'La valeur "{{ value }}" n\'est pas valide. Elle doit être une liste d\'Id zones séparés par des virgules ou vide.';
    public string $messageNotFound = 'La zone ID {{ id }} est introuvable.';
    public string $messageWrongTerritory = 'La zone ID {{ id }} n\'appartient pas au territoire "{{ territory }}".';

    public function getTargets(): string
    {
        return self::PROPERTY_CONSTRAINT;
    }
}
