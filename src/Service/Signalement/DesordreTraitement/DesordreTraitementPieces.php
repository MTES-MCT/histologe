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
        if (\array_key_exists($slugSansDetails1, $payload)
            && self::CHECKED_CRITERE_VALUE === $payload[$slugSansDetails1]
        ) {
            if ('piece_unique' === $payload['composition_logement_piece_unique']
                || (
                    \array_key_exists($slugCuisine, $payload)
                    && self::CHECKED_CRITERE_VALUE === $payload[$slugCuisine]
                    && \array_key_exists($slugPieceAVivre, $payload)
                    && self::CHECKED_CRITERE_VALUE === $payload[$slugPieceAVivre]
                    && \array_key_exists($slugSalleDeBain, $payload)
                    && self::CHECKED_CRITERE_VALUE === $payload[$slugSalleDeBain]
                )
            ) {
                $precision = $this->desordrePrecisionRepository->findOneBy(
                    ['desordrePrecisionSlug' => $slugTout.$suffixe]
                );
                $precisions[] = $precision;
            } else {
                if (\array_key_exists($slugCuisine, $payload)
                && self::CHECKED_CRITERE_VALUE === $payload[$slugCuisine]
                ) {
                    $precision = $this->desordrePrecisionRepository->findOneBy(
                        ['desordrePrecisionSlug' => $slugCuisine.$suffixe]
                    );
                    $precisions[] = $precision;
                }
                if (\array_key_exists($slugPieceAVivre, $payload)
                && self::CHECKED_CRITERE_VALUE === $payload[$slugPieceAVivre]
                ) {
                    $precision = $this->desordrePrecisionRepository->findOneBy(
                        ['desordrePrecisionSlug' => $slugPieceAVivre.$suffixe]
                    );
                    $precisions[] = $precision;
                }
                if (\array_key_exists($slugSalleDeBain, $payload)
                && self::CHECKED_CRITERE_VALUE === $payload[$slugSalleDeBain]
                ) {
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
