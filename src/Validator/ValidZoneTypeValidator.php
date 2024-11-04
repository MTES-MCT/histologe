<?php

namespace App\Validator;

use App\Entity\Enum\ZoneType;
use Symfony\Component\Form\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class ValidZoneTypeValidator extends ConstraintValidator
{
    public function validate($value, Constraint $constraint)
    {
        if (!$constraint instanceof ValidZoneType) {
            throw new UnexpectedTypeException($constraint, __NAMESPACE__.'\ValidZoneType');
        }
        /* @var ValidZoneType $constraint */
        if (null === $value || '' === $value) {
            return;
        }

        if (!\in_array($value, ZoneType::cases(), true)) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ value }}', $value)
                ->addViolation();
        }
    }
}
