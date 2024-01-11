<?php

namespace App\Service\Signalement\DesordreTraitement;

use App\Repository\DesordrePrecisionRepository;

class DesordreBatimentSecuriteEscalier implements DesordreTraitementInterface
{
    private const SLUG_DANGEREUX = 'desordres_batiment_securite_escalier_details_dangereux';
    private const SLUG_UTILISABLE = 'desordres_batiment_securite_escalier_details_utilisable';

    public function __construct(
        private readonly DesordrePrecisionRepository $desordrePrecisionRepository,
    ) {
    }

    public function findDesordresPrecisionsBy(array $payload, string $slug): array
    {
        $precisions = [];

        if (\array_key_exists(self::SLUG_DANGEREUX, $payload)) {
            if ('oui' === $payload[self::SLUG_DANGEREUX]) {
                $precision = $this->desordrePrecisionRepository->findOneBy(
                    ['desordrePrecisionSlug' => self::SLUG_DANGEREUX]
                );
                $precisions[] = $precision;
            } else {
                if (\array_key_exists(self::SLUG_UTILISABLE, $payload)
                    && 'oui' === $payload[self::SLUG_UTILISABLE]) {
                    $precision = $this->desordrePrecisionRepository->findOneBy(
                        ['desordrePrecisionSlug' => self::SLUG_UTILISABLE]
                    );
                    $precisions[] = $precision;
                }
            }
        }

        return $precisions;
    }
}
