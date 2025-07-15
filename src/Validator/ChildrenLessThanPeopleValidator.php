<?php

namespace App\Validator;

use App\Dto\Request\Signalement\SignalementDraftRequest;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

class ChildrenLessThanPeopleValidator extends ConstraintValidator
{
    public function validate(mixed $object, Constraint $constraint): void
    {
        if (!$constraint instanceof ChildrenLessThanPeople) {
            throw new UnexpectedValueException($constraint, ChildrenLessThanPeople::class);
        }

        if (!$object instanceof SignalementDraftRequest) {
            throw new UnexpectedValueException($object, SignalementDraftRequest::class);
        }

        $children = $object->getCompositionLogementNombreEnfants();
        $people = $object->getCompositionLogementNombrePersonnes();

        if (null === $children || null === $people) {
            return;
        }

        if ((int) $children > (int) $people) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ children }}', $children)
                ->setParameter('{{ people }}', $people)
                ->atPath('compositionLogementNombreEnfants')
                ->addViolation();
        }
    }
}
