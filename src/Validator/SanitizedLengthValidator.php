<?php

namespace App\Validator;

use App\Service\Sanitizer;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

class SanitizedLengthValidator extends ConstraintValidator
{
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof SanitizedLength) {
            throw new UnexpectedTypeException($constraint, SanitizedLength::class);
        }

        if (null === $value || '' === $value) {
            return;
        }

        if (!is_string($value)) {
            throw new UnexpectedValueException($value, 'string');
        }

        $sanitizedText = Sanitizer::sanitize($value);

        if (mb_strlen(trim(strip_tags($sanitizedText))) < $constraint->min) {
            $this->context
                ->buildViolation($constraint->message)
                ->setParameter('{{ limit }}', (string) $constraint->min)
                ->addViolation();
        }
    }
}
