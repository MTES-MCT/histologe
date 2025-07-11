<?php

namespace App\Service\Signalement\DesordreTraitement;

use App\Entity\DesordrePrecision;
use App\Repository\DesordrePrecisionRepository;

class DesordreLogementPlafondTropBas implements DesordreTraitementInterface
{
    public function __construct(
        private readonly DesordrePrecisionRepository $desordrePrecisionRepository,
    ) {
    }

    /**
     * @param array<string, string> $payload
     *
     * @return array<DesordrePrecision|null>
     */
    public function findDesordresPrecisionsBy(array $payload, string $slug): array
    {
        $precisions = [];

        if ('piece_unique' === $payload['composition_logement_piece_unique']) {
            $key = $slug.'_toutes_pieces';
            $precision = $this->desordrePrecisionRepository->findOneBy(
                ['desordrePrecisionSlug' => $key]
            );
            $precisions[] = $precision;
        } else {
            $suffixes = [
                '_piece_a_vivre',
                '_cuisine',
                '_salle_de_bain',
            ];

            foreach ($suffixes as $suffix) {
                $key = $slug.$suffix;
                if (\array_key_exists($key, $payload) && !empty($payload[$key])) {
                    $precision = $this->desordrePrecisionRepository->findOneBy(
                        ['desordrePrecisionSlug' => $key]
                    );
                    $precisions[] = $precision;
                }
            }
        }

        return $precisions;
    }
}
