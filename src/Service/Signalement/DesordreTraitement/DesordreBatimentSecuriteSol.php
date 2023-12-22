<?php

namespace App\Service\Signalement\DesordreTraitement;

use App\Repository\DesordrePrecisionRepository;

class DesordreBatimentSecuriteSol implements DesordreTraitementInterface
{
    public function __construct(
        private readonly DesordrePrecisionRepository $desordrePrecisionRepository,
    ) {
    }

    public function findDesordresPrecisionsBy(array $payload, string $slug): array
    {
        $precisions = [];

        $slugAbime = 'desordres_batiment_securite_sol_details_plancher_abime';
        if (isset($payload[$slugAbime])
            && 'oui' === $payload[$slugAbime]) {
            $precision = $this->desordrePrecisionRepository->findOneBy(
                ['desordrePrecisionSlug' => $slugAbime]
            );
            $precisions[] = $precision;
        }
        $slugEffondre = 'desordres_batiment_securite_sol_details_plancher_effondre';
        if (isset($payload[$slugEffondre])
            && 'oui' === $payload[$slugEffondre]) {
            $precision = $this->desordrePrecisionRepository->findOneBy(
                ['desordrePrecisionSlug' => $slugEffondre]
            );
            $precisions[] = $precision;
        }

        return $precisions;
    }
}
