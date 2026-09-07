<?php

namespace App\Validator\Behaviour;

use Symfony\Component\Validator\Context\ExecutionContextInterface;

trait RnbIdValidatorTrait
{
    public function validateRnbId(
        ?string $typeLogementNature,
        ?bool $isAdresseManuelle,
        ?string $rnbId,
        ?bool $noBuildingFound,
        string $fieldRnbId,
        ExecutionContextInterface $context,
    ): void {
        if ('autre' === $typeLogementNature || !$isAdresseManuelle || $noBuildingFound) {
            return;
        }

        if (null === $rnbId) {
            $context
                ->buildViolation('Veuillez sélectionner le bâtiment correspondant au logement.')
                ->atPath($fieldRnbId)
                ->addViolation();
        }
    }
}
