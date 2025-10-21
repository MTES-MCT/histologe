<?php

namespace App\Validator;

use App\Dto\Api\Request\DesordreRequest;
use App\Repository\DesordreCritereRepository;
use App\Repository\DesordrePrecisionRepository;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

class ApiDesordreRequestValidator extends ConstraintValidator
{
    public function __construct(
        private readonly DesordreCritereRepository $desordreCritereRepository,
        private readonly DesordrePrecisionRepository $desordrePrecisionRepository,
    ) {
    }

    /**
     * @param DesordreRequest $desordreRequest
     */
    public function validate($desordreRequest, Constraint $constraint): void
    {
        if (!$desordreRequest instanceof DesordreRequest) {
            throw new UnexpectedValueException($desordreRequest, DesordreRequest::class);
        }

        if (!$constraint instanceof ApiDesordreRequest) {
            throw new UnexpectedValueException($constraint, ApiDesordreRequest::class);
        }
        $desordrePrecision = $this->desordrePrecisionRepository->findWithCritereBySlug($desordreRequest->identifiant);
        if ($desordrePrecision) {
            $desordreCritere = $desordrePrecision->getDesordreCritere();
        } else {
            $desordreCritere = $this->desordreCritereRepository->findWithPrecisionsBySlug($desordreRequest->identifiant);
        }

        if (!$desordreCritere) {
            $this->context
                ->buildViolation('Le désordre "'.$desordreRequest->identifiant.'" est invalide.')
                ->atPath('identifiant')
                ->addViolation();

            return;
        }
        $existingPrecisionSlugs = array_map(fn ($precision) => $precision->getDesordrePrecisionSlug(), $desordreCritere->getDesordrePrecisions()->toArray());
        if ($desordreCritere->getDesordrePrecisions()->count() > 1) {
            // controle de la précision obligatoire
            if (0 === count($desordreRequest->precisions)) {
                $this->context
                    ->buildViolation('Au moins une précision doit être fournie pour le désordre "'.$desordreRequest->identifiant.'"')
                    ->atPath('identifiant')
                    ->addViolation();
            }
            $selectedPrecisions = [];
            foreach ($desordreRequest->precisions as $index => $precisionSlug) {
                // contrôle de la cohérence entre le critere et les précisions
                if (!in_array($precisionSlug, $existingPrecisionSlugs)) {
                    $this->context
                        ->buildViolation('La précision "'.$precisionSlug.'" ne correspond pas au désordre "'.$desordreRequest->identifiant.'"')
                        ->atPath('precisions')
                        ->addViolation();
                }
                // controle des doublons de précisions
                if (in_array($precisionSlug, $selectedPrecisions)) {
                    $this->context
                        ->buildViolation('La précision "'.$precisionSlug.'" est fournie en doublon pour le désordre "'.$desordreRequest->identifiant.'"')
                        ->atPath('precisions['.$index.']')
                        ->addViolation();
                }
                $selectedPrecisions[] = $precisionSlug;
                // controle des précisions uniques
                $precision = $this->desordrePrecisionRepository->findOneBy(['desordrePrecisionSlug' => $precisionSlug]);
                if ($precision && $precision->getconfigIsUnique() && count($desordreRequest->precisions) > 1) {
                    $this->context
                        ->buildViolation('La précision "'.$precisionSlug.'" ne doit pas être cumulée avec d\'autres précisions pour le désordre "'.$desordreRequest->identifiant.'"')
                        ->atPath('precisions['.$index.']')
                        ->addViolation();
                }
            }
            // on pourrais controller aussi les précisions incompatibles entre elles :
            // ex : Diagnostic plomb / amiante disponible : oui et Diagnostic plomb / amiante disponible : non)
            // cela concerne uniquement 3 désordres, j'ai laissé de coté pour l'instant
        }
        // controle des precisions libres
        foreach ($desordreRequest->precisionLibres as $index => $precisionLibre) {
            $precisionLibreType = $desordreCritere->getConfigPrecisionLibreType();
            if ('critere' === $precisionLibreType) {
                if ($precisionLibre->identifiant !== $desordreRequest->identifiant) {
                    $this->context
                        ->buildViolation('Le désordre "'.$desordreRequest->identifiant.'" ne correspond pas avec la précision libre "'.$precisionLibre->identifiant.'".')
                        ->atPath('precisionLibres['.$index.']')
                        ->addViolation();
                }
            } elseif ('precisions' === $precisionLibreType) {
                if (!in_array($precisionLibre->identifiant, $existingPrecisionSlugs)) {
                    $this->context
                        ->buildViolation('Le désordre "'.$desordreRequest->identifiant.'" ne correspond pas avec la précision libre "'.$precisionLibre->identifiant.'".')
                        ->atPath('precisionLibres['.$index.']')
                        ->addViolation();
                }
                $precisionSlugs = [];
                foreach ($desordreRequest->precisions as $precisionSlug) {
                    $precisionSlugs[] = $precisionSlug;
                }
                if (!in_array($precisionLibre->identifiant, $precisionSlugs)) {
                    $this->context
                        ->buildViolation('La précision libre "'.$precisionLibre->identifiant.'" ne correspond pas avec les précisions fournies pour le désordre "'.$desordreRequest->identifiant.'".')
                        ->atPath('precisionLibres['.$index.']')
                        ->addViolation();
                }
            } else {
                $this->context
                    ->buildViolation('Le désordre "'.$desordreRequest->identifiant.'" n\'accepte pas de description libre.')
                    ->atPath('precisionLibres['.$index.']')
                    ->addViolation();
            }
        }
    }
}
