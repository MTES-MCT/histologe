<?php

namespace App\Validator;

use Symfony\Component\Validator\Constraint;

#[\Attribute]
class ValidFiles extends Constraint
{
    public string $message = 'Le fichier avec l\'UUID "{{ uuid }}" est invalide ou inexistant.';

    public function __construct(?string $message = null)
    {
        parent::__construct([]);
        if ($message) {
            $this->message = $message;
        }
    }
}
