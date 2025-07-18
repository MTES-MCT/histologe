<?php

namespace App\Validator;

use Symfony\Component\Validator\Constraint;

#[\Attribute(\Attribute::TARGET_CLASS)]
class ValueLessThanOtherValue extends Constraint
{
    public string $property;
    public string $otherProperty;
    public string $message;

    public function __construct(
        string $property,
        string $otherProperty,
        ?string $message = null,
        ...$options,
    ) {
        parent::__construct($options);
        $this->property = $property;
        $this->otherProperty = $otherProperty;
        $this->message = $message ?? 'La valeur de "{{ property }}" ({{ value }}) doit être inférieure ou égale à celle de "{{ otherProperty }}" ({{ otherValue }}).';
    }

    public function getTargets(): string
    {
        return self::CLASS_CONSTRAINT;
    }
}
