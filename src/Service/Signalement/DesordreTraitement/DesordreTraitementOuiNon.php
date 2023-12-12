<?php

namespace App\Service\Signalement\DesordreTraitement;

use App\Repository\DesordrePrecisionRepository;

class DesordreTraitementOuiNon implements DesordreTraitementInterface
{
    public function __construct(
        private readonly DesordrePrecisionRepository $desordrePrecisionRepository,
    ) {
    }

    public function findDesordresPrecisionsBy(array $payload, string $slug): array
    {
        $precisions = [];

        if (isset($payload[$slug])) {
            if ('oui' === $payload[$slug]) {
                $precision = $this->desordrePrecisionRepository->findOneBy(
                    ['desordrePrecisionSlug' => $slug.'_oui']
                );
            } else {
                $precision = $this->desordrePrecisionRepository->findOneBy(
                    ['desordrePrecisionSlug' => $slug.'_non']
                );
            }
            $precisions[] = $precision;
        }

        return $precisions;
    }
}
