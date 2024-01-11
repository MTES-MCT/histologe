<?php

namespace App\Service\Signalement\DesordreTraitement;

use App\Repository\DesordrePrecisionRepository;

class DesordreBatimentSecuriteSol implements DesordreTraitementInterface
{
    private const SLUG_EFFONDRE = 'desordres_batiment_securite_sol_details_plancher_effondre';
    private const SLUG_ABIME = 'desordres_batiment_securite_sol_details_plancher_abime';

    public function __construct(
        private readonly DesordrePrecisionRepository $desordrePrecisionRepository,
    ) {
    }

    public function findDesordresPrecisionsBy(array $payload, string $slug): array
    {
        $precisions = [];

        if (\array_key_exists(self::SLUG_EFFONDRE, $payload)) {
            if ('oui' === $payload[self::SLUG_EFFONDRE]) {
                $precision = $this->desordrePrecisionRepository->findOneBy(
                    ['desordrePrecisionSlug' => self::SLUG_EFFONDRE]
                );
                $precisions[] = $precision;
            } else {
                if (\array_key_exists(self::SLUG_ABIME, $payload)
                    && 'oui' === $payload[self::SLUG_ABIME]) {
                    $precision = $this->desordrePrecisionRepository->findOneBy(
                        ['desordrePrecisionSlug' => self::SLUG_ABIME]
                    );
                    $precisions[] = $precision;
                }
            }
        }

        return $precisions;
    }
}
