<?php

namespace App\Validator;

use Symfony\Component\Validator\Constraint;

#[\Attribute]
class SanitizedLength extends Constraint
{
    public string $message = 'Le texte doit contenir au moins {{ limit }} caractères après sanitation.';
    public int $min;

    public function __construct(int $min, ?string $message = null, ?array $groups = null)
    {
        parent::__construct([
            'groups' => $groups,
        ]);
        $this->min = $min;
        if ($message) {
            $this->message = $message;
        }
    }
}
