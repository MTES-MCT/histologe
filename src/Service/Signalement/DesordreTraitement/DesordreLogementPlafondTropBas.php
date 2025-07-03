<?php

namespace App\Service\Signalement\DesordreTraitement;

use App\Entity\DesordrePrecision;
use App\Repository\DesordrePrecisionRepository;

class DesordreLogementPlafondTropBas implements DesordreTraitementInterface
{
    public function __construct(
        private readonly DesordrePrecisionRepository $desordrePrecisionRepository,
        private readonly DesordreTraitementPieces $desordreTraitementPieces,
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

        $suffixes = [
            '_piece_a_vivre',
            '_cuisine',
            '_salle_de_bain',
            '_toutes_pieces',
            '_piece_unique',
        ];

        foreach ($suffixes as $suffix) {
            $key = $slug.$suffix;
            if (\array_key_exists($key, $payload)) {
                $precision = $this->desordrePrecisionRepository->findOneBy(
                    ['desordrePrecisionSlug' => $key]
                );
                $precisions[] = $precision;
            }
        }

        return $precisions;
    }
}
