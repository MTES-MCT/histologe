<?php

namespace App\Validator\Behaviour;

use Symfony\Component\Validator\Context\ExecutionContextInterface;

trait EtageValidatorTrait
{
    public function validateEtage(?string $typeLogement, ?string $valueEtage, string $fieldEtage, ?string $valuePrecision, string $fieldPrecision, ExecutionContextInterface $context): void
    {
        if (null === $typeLogement) {
            return;
        }

        if ('appartement' === $typeLogement && null === $valueEtage) {
            $context
                ->buildViolation('Le champ étage est obligatoire si le logement est un appartement.')
                ->atPath($fieldEtage)
                ->addViolation();
        }

        if ('AUTRE' === $valueEtage && null === $valuePrecision) {
            $context
                ->buildViolation('Le champ précision de l\'étage est obligatoire si l\'étage est "AUTRE".')
                ->atPath($fieldPrecision)
                ->addViolation();
        }
    }
}
