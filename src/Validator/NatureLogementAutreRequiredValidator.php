<?php

namespace App\Validator;

use App\Dto\ServiceSecours\FormServiceSecoursStep2;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

class NatureLogementAutreRequiredValidator extends ConstraintValidator
{
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$value instanceof FormServiceSecoursStep2) {
            throw new UnexpectedValueException($value, FormServiceSecoursStep2::class);
        }

        if (!$constraint instanceof NatureLogementAutreRequired) {
            throw new UnexpectedValueException($constraint, NatureLogementAutreRequired::class);
        }

        // Si la nature du logement est "autre", alors le champ natureLogementAutre est obligatoire
        if ('autre' === $value->natureLogement && empty($value->natureLogementAutre)) {
            $this->context
                ->buildViolation($constraint->message)
                ->atPath('natureLogementAutre')
                ->addViolation();
        }
    }
}
