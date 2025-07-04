<?php

namespace App\Validator;

use Symfony\Component\Form\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class PartnerToExcludeValidator extends ConstraintValidator
{
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof PartnerToExclude) {
            throw new UnexpectedTypeException($constraint, __NAMESPACE__.'\PartnerToExclude');
        }
        /* @var PartnerToExclude $constraint */
        if (null === $value || '' === $value) {
            return;
        }

        foreach ($value as $code) {
            if (!preg_match('/^\d*$/', mb_trim($code))) {
                $this->context->buildViolation($constraint->message)
                    ->setParameter('{{ value }}', implode(',', $value))
                    ->addViolation();

                return;
            }
        }
    }
}
