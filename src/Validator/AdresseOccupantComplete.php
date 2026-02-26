<?php

namespace App\Validator;

use Symfony\Component\Validator\Constraint;

#[\Attribute]
class AdresseOccupantComplete extends Constraint
{
    public string $messageAdresse = 'L\'adresse (numéro et voie) est obligatoire.';
    public string $messageCp = 'Le code postal est obligatoire.';
    public string $messageVille = 'La ville est obligatoire.';

    public function getTargets(): string
    {
        return self::CLASS_CONSTRAINT;
    }
}
