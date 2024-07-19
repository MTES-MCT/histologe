<?php

namespace App\Validator;

use App\Entity\Enum\PartnerType;
use Symfony\Component\Form\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class ValidPartnerTypeValidator extends ConstraintValidator
{
    public function validate($value, Constraint $constraint)
    {
        if (!$constraint instanceof ValidPartnerType) {
            throw new UnexpectedTypeException($constraint, __NAMESPACE__.'\ValidPartnerType');
        }
        /* @var ValidPartnerType $constraint */
        if (null === $value || '' === $value) {
            return;
        }

        if (!\in_array($value, PartnerType::cases(), true)) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ value }}', $value)
                ->addViolation();
        }
    }
}
