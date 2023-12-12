<?php

namespace App\Service\Signalement\DesordreTraitement;

use App\Repository\DesordrePrecisionRepository;

class DesordreTraitementNuisibles implements DesordreTraitementInterface
{
    public function __construct(
        private readonly DesordrePrecisionRepository $desordrePrecisionRepository,
    ) {
    }

    public function findDesordresPrecisionsBy(array $payload, string $slug): array
    {
        $precisions = [];

        if (isset($payload[$slug.'_details_date'])) {
            if ('before_movein' === $payload[$slug.'_details_date']) {
                $precision = $this->desordrePrecisionRepository->findOneBy(
                    ['desordrePrecisionSlug' => $slug.'_details_date_before_movein']
                );
            } else {
                $precision = $this->desordrePrecisionRepository->findOneBy(
                    ['desordrePrecisionSlug' => $slug.'_details_date_after_movein']
                );
            }
            $precisions[] = $precision;
        }

        return $precisions;
    }
}
