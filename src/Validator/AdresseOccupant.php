<?php

namespace App\Validator;

use Symfony\Component\Validator\Constraint;

#[\Attribute]
class AdresseOccupant extends Constraint
{
    public string $messageAdresse = 'L\'adresse (numéro et voie) est obligatoire.';
    public string $messageCp = 'Le code postal est obligatoire.';
    public string $messageVille = 'La ville est obligatoire.';
    public string $messageInsee = 'Le territoire n\'est pas actif pour le code INSEE "{{ code }}".';
    public string $messagePostalCode = 'Le territoire n\'est pas actif pour le code postal "{{ code }}".';

    public function getTargets(): string
    {
        return self::CLASS_CONSTRAINT;
    }
}
