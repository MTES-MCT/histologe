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
            $existingPrecisions = [];
            foreach ($desordreRequest->precisions as $index => $precisionSlug) {
                // contrôle de la cohérence entre le critere et les précisions
                if (!in_array($precisionSlug, $existingPrecisionSlugs)) {
                    $this->context
                        ->buildViolation('La précision "'.$precisionSlug.'" ne correspond pas au désordre "'.$desordreRequest->identifiant.'"')
                        ->atPath('precisions')
                        ->addViolation();
                }
                // controle des doublons de précisions
                if (in_array($precisionSlug, $existingPrecisions)) {
                    $this->context
                        ->buildViolation('La précision "'.$precisionSlug.'" est fournie en doublon pour le désordre "'.$desordreRequest->identifiant.'"')
                        ->atPath('precisions['.$index.']')
                        ->addViolation();
                }
                $existingPrecisions[] = $precisionSlug;
                // controle des précisions uniques
                $precision = $this->desordrePrecisionRepository->findOneBy(['desordrePrecisionSlug' => $precisionSlug]);
                if ($precision->getconfigIsUnique() && count($desordreRequest->precisions) > 1) {
                    $this->context
                        ->buildViolation('La précision "'.$precisionSlug.'" ne doit pas être cumulée avec d\'autres précisions pour le désordre "'.$desordreRequest->identifiant.'"')
                        ->atPath('precisions['.$index.']')
                        ->addViolation();
                }
            }
        }
        // TODO : controle des descriptions libres -> parametrer en base de données la config des désordre et précisions attendant une description libre ?
        // TODO : controle des précisions s'excluant mutuellement -> parametrer en base de données la config des précisions s'excluant mutuellement ?
    }
}
