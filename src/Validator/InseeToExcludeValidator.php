<?php

namespace App\Validator;

use Symfony\Component\Form\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class InseeToExcludeValidator extends ConstraintValidator
{
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof InseeToExclude) {
            throw new UnexpectedTypeException($constraint, __NAMESPACE__.'\InseeToExclude');
        }
        /* @var InseeToExclude $constraint */
        if (null === $value || '' === $value) {
            return;
        }

        foreach ($value as $code) {
            if (!preg_match('/^\d{5}$/', mb_trim($code))) {
                $this->context->buildViolation($constraint->message)
                    ->setParameter('{{ value }}', implode(',', $value))
                    ->addViolation();

                return;
            }
        }
    }
}
