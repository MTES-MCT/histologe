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
                ->buildViolation('Le désordre " '.$desordreRequest->identifiant.' " est invalide.')
                ->atPath('identifiant')
                ->addViolation();

            return;
        }
        $existingPrecisionSlugs = array_map(fn ($precision) => $precision->getDesordrePrecisionSlug(), $desordreCritere->getDesordrePrecisions()->toArray());
        if ($desordreCritere->getDesordrePrecisions()->count() > 1) {
            // controle de la précision obligatoire
            if (0 === count($desordreRequest->precisions)) {
                $this->context
                    ->buildViolation('Au moins une précision doit être fournie pour le désordre " '.$desordreRequest->identifiant.' "')
                    ->atPath('identifiant')
                    ->addViolation();
            }
            // contrôle de la cohérence entre le critere et les précisions
            foreach ($desordreRequest->precisions as $precision) {
                if (!in_array($precision, $existingPrecisionSlugs)) {
                    $this->context
                        ->buildViolation('La précision " '.$precision.' " ne correspond pas au désordre " '.$desordreRequest->identifiant.' "')
                        ->atPath('precisions')
                        ->addViolation();
                }
            }
        }

        // TODO : controle des doublons de précisions
        // TODO : controle des descriptions libres -> parametrer en base de données la config des désordre et précisions attendant une description libre ?
        // TODO : controle des précisions uniques -> parametrer en base de données la config des précisions devant être un choix unique ?
        // TODO : controle des précisions s'excluant mutuellement -> parametrer en base de données la config des précisions s'excluant mutuellement ?
    }
}
