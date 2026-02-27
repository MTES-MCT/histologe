<?php

namespace App\Validator;

use App\Dto\ServiceSecours\FormServiceSecoursStep2;
use App\Service\Signalement\ZipcodeProvider;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

class AdresseOccupantValidator extends ConstraintValidator
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

        if (!$constraint instanceof AdresseOccupant) {
            throw new UnexpectedValueException($constraint, AdresseOccupant::class);
        }

        // Validation 1: Vérifier que l'adresse est complète
        $hasManualAddress = !empty($value->adresseOccupant)
            || !empty($value->cpOccupant)
            || !empty($value->villeOccupant);

        if (!$hasManualAddress && !empty($value->adresseCompleteOccupant)) {
            // L'utilisateur utilise l'autocomplete, pas besoin de valider les champs manuels
            // Mais on continue pour vérifier le territoire
        } else {
            // Si on est en mode adresse manuelle, tous les champs sont requis
            $hasViolation = false;

            if (empty($value->adresseOccupant)) {
                $this->context
                    ->buildViolation($constraint->messageAdresse)
                    ->atPath('adresseCompleteOccupant')
                    ->addViolation();
                $hasViolation = true;
            }

            if (empty($value->cpOccupant)) {
                $this->context
                    ->buildViolation($constraint->messageCp)
                    ->atPath('adresseCompleteOccupant')
                    ->addViolation();
                $hasViolation = true;
            }

            if (empty($value->villeOccupant)) {
                $this->context
                    ->buildViolation($constraint->messageVille)
                    ->atPath('adresseCompleteOccupant')
                    ->addViolation();
                $hasViolation = true;
            }

            // S'il y a des violations, on s'arrête ici
            if ($hasViolation) {
                return;
            }
        }

        // Validation 2: Vérifier que le territoire est actif
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
