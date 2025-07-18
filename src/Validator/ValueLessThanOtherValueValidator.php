<?php

namespace App\Validator;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

class ValueLessThanOtherValueValidator extends ConstraintValidator
{
    public function validate(mixed $object, Constraint $constraint): void
    {
        if (!$constraint instanceof ValueLessThanOtherValue) {
            throw new UnexpectedValueException($constraint, ValueLessThanOtherValue::class);
        }

        $getter = 'get'.ucfirst($constraint->property);
        $otherGetter = 'get'.ucfirst($constraint->otherProperty);

        if (!method_exists($object, $getter) || !method_exists($object, $otherGetter)) {
            return;
        }

        $value = $object->$getter();
        $otherValue = $object->$otherGetter();

        if (null === $value || null === $otherValue) {
            return;
        }

        if ((int) $value > (int) $otherValue) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ property }}', $constraint->property)
                ->setParameter('{{ value }}', $value)
                ->setParameter('{{ otherProperty }}', $constraint->otherProperty)
                ->setParameter('{{ otherValue }}', $otherValue)
                ->atPath($constraint->property)
                ->addViolation();
        }
    }
}
