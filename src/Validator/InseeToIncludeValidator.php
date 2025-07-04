<?php

namespace App\Validator;

use Symfony\Component\Form\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class InseeToIncludeValidator extends ConstraintValidator
{
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof InseeToInclude) {
            throw new UnexpectedTypeException($constraint, __NAMESPACE__.'\InseeToInclude');
        }
        /* @var InseeToInclude $constraint */
        if ('' === $value) {
            return;
        }

        if (null === $value) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ value }}', 'null')
                ->addViolation();

            return;
        }

        $inseeCodes = explode(',', $value);
        foreach ($inseeCodes as $code) {
            if (!preg_match('/^\d{5}$/', mb_trim($code))) {
                $this->context->buildViolation($constraint->message)
                    ->setParameter('{{ value }}', $value)
                    ->addViolation();

                return;
            }
        }
    }
}
