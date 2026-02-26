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
        $postalCode = $value->cpOccupant;
        $territory = null;
        $usedInseeCode = false;

        // Essayer d'abord avec le code INSEE si disponible
        if (!empty($inseeCode)) {
            $territory = $this->zipcodeProvider->getTerritoryByInseeCode($inseeCode);
            $usedInseeCode = true;
        }

        // Si pas de territoire trouvé avec le code INSEE, essayer avec le code postal
        if (!$territory && !empty($postalCode)) {
            $territory = $this->zipcodeProvider->getTerritoryByPostalCode($postalCode);
            $usedInseeCode = false;
        }

        // Si aucun territoire trouvé ou territoire inactif, ajouter une violation
        if (!$territory || !$territory->isIsActive()) {
            $message = $usedInseeCode ? $constraint->messageInsee : $constraint->messagePostalCode;
            $code = $usedInseeCode ? $inseeCode : $postalCode;

            $this->context
                ->buildViolation($message)
                ->setParameter('{{ code }}', $code ?? '')
                ->atPath('adresseCompleteOccupant')
                ->addViolation();
        }
    }
}
