<?php

namespace App\Validator;

use App\Entity\Enum\ProfileDeclarant;
use Symfony\Component\Form\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class ValidProfileDeclarantValidator extends ConstraintValidator
{
    public function validate($value, Constraint $constraint)
    {
        if (!$constraint instanceof ValidProfileDeclarant) {
            throw new UnexpectedTypeException($constraint, __NAMESPACE__.'\ValidProfileDeclarant');
        }
        /* @var ValidProfileDeclarant $constraint */
        if (null === $value || '' === $value) {
            return;
        }

        $validValues = ProfileDeclarant::getListWithGroup();

        if (!\in_array($value, $validValues, true)) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ value }}', $value)
                ->addViolation();
        }
    }
}
