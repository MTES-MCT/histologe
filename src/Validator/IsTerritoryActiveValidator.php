<?php

namespace App\Validator;

use App\Dto\Request\Signalement\SignalementDraftRequest;
use App\Service\Signalement\PostalCodeHomeChecker;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

class IsTerritoryActiveValidator extends ConstraintValidator
{
    public function __construct(
        private readonly PostalCodeHomeChecker $postalCodeHomeChecker,
    ) {
    }

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$value instanceof SignalementDraftRequest) {
            throw new UnexpectedValueException($value, SignalementDraftRequest::class);
        }

        if (!$constraint instanceof IsTerritoryActive) {
            throw new UnexpectedValueException($constraint, IsTerritoryActive::class);
        }

        $postalCode = $value->getAdresseLogementAdresseDetailCodePostal();
        $inseeCode = $value->getAdresseLogementAdresseDetailInsee();
        $inseeCode = $this->postalCodeHomeChecker->normalizeInseeCode($postalCode, $inseeCode);
        if ($postalCode && $inseeCode) {
            if (!$this->postalCodeHomeChecker->isActiveByInseeCode($inseeCode)) {
                $message = 'Le territoire n\'est pas actif pour le code postal "'.$postalCode.'" et le code INSEE "'.$inseeCode.'".';
                $this->context
                    ->buildViolation($message)
                    ->atPath('adresseLogementAdresseDetailCodePostal')
                    ->addViolation();
            }
        }
    }
}
