<?php

namespace App\Validator;

use App\Dto\ServiceSecours\FormServiceSecoursStep2;
use App\Service\Signalement\ZipcodeProvider;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

class InseeOccupantIsActiveValidator extends ConstraintValidator
{
    public function __construct(
        private readonly ZipcodeProvider $zipcodeProvider,
    ) {
    }

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$value instanceof FormServiceSecoursStep2) {
            throw new UnexpectedValueException($value, FormServiceSecoursStep2::class);
        }

        if (!$constraint instanceof InseeOccupantIsActive) {
            throw new UnexpectedValueException($constraint, InseeOccupantIsActive::class);
        }

        $inseeCode = $value->inseeOccupant;

        if (null === $inseeCode || '' === $inseeCode) {
            return;
        }

        $territory = $this->zipcodeProvider->getTerritoryByInseeCode($inseeCode);

        if (!$territory || !$territory->isIsActive()) {
            $this->context
                ->buildViolation($constraint->message)
                ->setParameter('{{ inseeCode }}', $inseeCode)
                ->atPath('adresseCompleteOccupant')
                ->addViolation();
        }
    }
}
