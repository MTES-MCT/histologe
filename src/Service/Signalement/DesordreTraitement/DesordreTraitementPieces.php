<?php

namespace App\Service\Signalement\DesordreTraitement;

use App\Repository\DesordrePrecisionRepository;
use Doctrine\Common\Collections\ArrayCollection;

class DesordreTraitementPieces
{
    public function __construct(
        private readonly DesordrePrecisionRepository $desordrePrecisionRepository,
    ) {
    }

    public function getPrecisionsPieces(string $slug, array $payload): ArrayCollection
    {
        $precisions = new ArrayCollection();

        $slugCuisine = $slug.'_cuisine';
        $slugPieceAVivre = $slug.'_piece_a_vivre';
        $slugSalleDeBain = $slug.'_salle_de_bain';
        $slugTout = $slug.'_tout';
        if (isset($payload[$slug])
        && 1 === $payload[$slug]) {
            if ('piece_unique' === $payload['composition_logement_piece_unique']
            || (1 === $payload[$slugCuisine]
                && 1 === $payload[$slugPieceAVivre]
                && 1 === $payload[$slugSalleDeBain])
            ) {
                $precision = $this->desordrePrecisionRepository->findOneBy(
                    ['desordrePrecisionSlug' => $slugTout]
                );
                $precisions->add($precision);
            } else {
                if (1 === $payload[$slugCuisine]) {
                    $precision = $this->desordrePrecisionRepository->findOneBy(
                        ['desordrePrecisionSlug' => $slugCuisine]
                    );
                    $precisions->add($precision);
                }
                if (1 === $payload[$slugPieceAVivre]) {
                    $precision = $this->desordrePrecisionRepository->findOneBy(
                        ['desordrePrecisionSlug' => $slugPieceAVivre]
                    );
                    $precisions->add($precision);
                }
                if (1 === $payload[$slugSalleDeBain]) {
                    $precision = $this->desordrePrecisionRepository->findOneBy(
                        ['desordrePrecisionSlug' => $slugSalleDeBain]
                    );
                    $precisions->add($precision);
                }
            }
        }

        return $precisions;
    }
}
