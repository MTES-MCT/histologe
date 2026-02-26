<?php

namespace App\Validator;

use App\Dto\ServiceSecours\FormServiceSecoursStep2;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

class AdresseOccupantCompleteValidator extends ConstraintValidator
{
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$value instanceof FormServiceSecoursStep2) {
            throw new UnexpectedValueException($value, FormServiceSecoursStep2::class);
        }

        if (!$constraint instanceof AdresseOccupantComplete) {
            throw new UnexpectedValueException($constraint, AdresseOccupantComplete::class);
        }

        // Validation uniquement si au moins un des champs d'adresse manuelle est rempli
        // ou si adresseCompleteOccupant est vide (ce qui signifie qu'on utilise l'adresse manuelle)
        $hasManualAddress = !empty($value->adresseOccupant)
            || !empty($value->cpOccupant)
            || !empty($value->villeOccupant);

        if (!$hasManualAddress && !empty($value->adresseCompleteOccupant)) {
            // L'utilisateur utilise l'autocomplete, pas besoin de valider les champs manuels
            return;
        }

        // Si on est en mode adresse manuelle, tous les champs sont requis
        if (empty($value->adresseOccupant)) {
            $this->context
                ->buildViolation($constraint->messageAdresse)
                ->atPath('adresseCompleteOccupant')
                ->addViolation();
        }

        if (empty($value->cpOccupant)) {
            $this->context
                ->buildViolation($constraint->messageCp)
                ->atPath('adresseCompleteOccupant')
                ->addViolation();
        }

        if (empty($value->villeOccupant)) {
            $this->context
                ->buildViolation($constraint->messageVille)
                ->atPath('adresseCompleteOccupant')
                ->addViolation();
        }
    }
}
