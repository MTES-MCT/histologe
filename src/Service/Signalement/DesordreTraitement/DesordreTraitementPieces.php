<?php

namespace App\Service\Signalement\DesordreTraitement;

use App\Repository\DesordrePrecisionRepository;
use Doctrine\Common\Collections\ArrayCollection;

class DesordreTraitementPieces implements DesordreTraitementInterface
{
    public function __construct(
        private readonly DesordrePrecisionRepository $desordrePrecisionRepository,
    ) {
    }

    public function process(array $payload, string $slug, string $suffixe = ''): ArrayCollection
    {
        $precisions = new ArrayCollection();

        $pattern = '/^(.*)_details$/';

        $slugSansDetails1 = preg_replace($pattern, '$1', $slug);

        $slugCuisine = $slug.'_pieces_cuisine';
        $slugPieceAVivre = $slug.'_pieces_piece_a_vivre';
        $slugSalleDeBain = $slug.'_pieces_salle_de_bain';
        $slugTout = $slug.'_pieces_tout';
        if (isset($payload[$slugSansDetails1]) && 1 === $payload[$slugSansDetails1]) {
            if ('piece_unique' === $payload['composition_logement_piece_unique']
            || (
                isset($payload[$slugCuisine]) && 1 === $payload[$slugCuisine]
                && isset($payload[$slugPieceAVivre]) && 1 === $payload[$slugPieceAVivre]
                && isset($payload[$slugSalleDeBain]) && 1 === $payload[$slugSalleDeBain]
            )
            ) {
                $precision = $this->desordrePrecisionRepository->findOneBy(
                    ['desordrePrecisionSlug' => $slugTout.$suffixe]
                );
                $precisions->add($precision);
            } else {
                if (isset($payload[$slugCuisine]) && 1 === $payload[$slugCuisine]) {
                    $precision = $this->desordrePrecisionRepository->findOneBy(
                        ['desordrePrecisionSlug' => $slugCuisine.$suffixe]
                    );
                    $precisions->add($precision);
                }
                if (isset($payload[$slugPieceAVivre]) && 1 === $payload[$slugPieceAVivre]) {
                    $precision = $this->desordrePrecisionRepository->findOneBy(
                        ['desordrePrecisionSlug' => $slugPieceAVivre.$suffixe]
                    );
                    $precisions->add($precision);
                }
                if (isset($payload[$slugSalleDeBain]) && 1 === $payload[$slugSalleDeBain]) {
                    $precision = $this->desordrePrecisionRepository->findOneBy(
                        ['desordrePrecisionSlug' => $slugSalleDeBain.$suffixe]
                    );
                    $precisions->add($precision);
                }
            }
        }

        return $precisions;
    }
}
