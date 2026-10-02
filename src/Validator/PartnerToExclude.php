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
    public string $messageNotFound = 'Le partenaire ID {{ id }} est introuvable.';
    public string $messageArchived = 'Le partenaire ID {{ id }} est archivé.';
    public string $messageWrongTerritory = 'Le partenaire ID {{ id }} n\'appartient pas au territoire "{{ territory }}".';
    public string $messageWrongType = 'Le partenaire ID {{ id }} n\'a pas le type "{{ type }}".';

    public function getTargets(): string
    {
        return self::PROPERTY_CONSTRAINT;
    }
}
