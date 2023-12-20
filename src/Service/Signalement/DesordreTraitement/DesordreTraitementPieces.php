<?php

namespace App\Service\Signalement\DesordreTraitement;

use App\Repository\DesordrePrecisionRepository;

class DesordreTraitementPieces implements DesordreTraitementInterface
{
    private const CHECKED_CRITERE_VALUE = 1;

    public function __construct(
        private readonly DesordrePrecisionRepository $desordrePrecisionRepository,
    ) {
    }

    public function findDesordresPrecisionsBy(array $payload, string $slug, string $suffixe = ''): array
    {
        $precisions = [];

        $pattern = '/^(.*)_details$/';

        $slugSansDetails1 = preg_replace($pattern, '$1', $slug);

        $slugCuisine = $slug.'_pieces_cuisine';
        $slugPieceAVivre = $slug.'_pieces_piece_a_vivre';
        $slugSalleDeBain = $slug.'_pieces_salle_de_bain';
        $slugTout = $slug.'_pieces_tout';
        if (isset($payload[$slugSansDetails1]) && self::CHECKED_CRITERE_VALUE === $payload[$slugSansDetails1]) {
            if ('piece_unique' === $payload['composition_logement_piece_unique']
            || (
                isset($payload[$slugCuisine]) && self::CHECKED_CRITERE_VALUE === $payload[$slugCuisine]
                && isset($payload[$slugPieceAVivre]) && self::CHECKED_CRITERE_VALUE === $payload[$slugPieceAVivre]
                && isset($payload[$slugSalleDeBain]) && self::CHECKED_CRITERE_VALUE === $payload[$slugSalleDeBain]
            )
            ) {
                $precision = $this->desordrePrecisionRepository->findOneBy(
                    ['desordrePrecisionSlug' => $slugTout.$suffixe]
                );
                $precisions[] = $precision;
            } else {
                if (isset($payload[$slugCuisine]) && self::CHECKED_CRITERE_VALUE === $payload[$slugCuisine]) {
                    $precision = $this->desordrePrecisionRepository->findOneBy(
                        ['desordrePrecisionSlug' => $slugCuisine.$suffixe]
                    );
                    $precisions[] = $precision;
                }
                if (isset($payload[$slugPieceAVivre]) && self::CHECKED_CRITERE_VALUE === $payload[$slugPieceAVivre]) {
                    $precision = $this->desordrePrecisionRepository->findOneBy(
                        ['desordrePrecisionSlug' => $slugPieceAVivre.$suffixe]
                    );
                    $precisions[] = $precision;
                }
                if (isset($payload[$slugSalleDeBain]) && self::CHECKED_CRITERE_VALUE === $payload[$slugSalleDeBain]) {
                    $precision = $this->desordrePrecisionRepository->findOneBy(
                        ['desordrePrecisionSlug' => $slugSalleDeBain.$suffixe]
                    );
                    $precisions[] = $precision;
                }
            }
        }

        return $precisions;
    }
}
