<?php

namespace App\Specification\Signalement;

use App\Entity\Model\SituationFoyer;
use App\Entity\Model\TypeCompositionLogement;

class SuroccupationSpecification
{
    private const ALLOCATAIRE_SUPERFICIE_1_OCCUPANT = 9;
    private const ALLOCATAIRE_SUPERFICIE_2_OCCUPANTS = 16;
    private const ALLOCATAIRE_SUPERFICIE_BASE = 16;
    private const ALLOCATAIRE_SUPERFICIE_PER_OCCUPANT = 9;
    private const NON_ALLOCATAIRE_MIN_PIECES_PER_OCCUPANT = 2;
    private ?string $slug = null;

    public function isSatisfiedBy(
        SituationFoyer $situationFoyer,
        TypeCompositionLogement $typeCompositionLogement
    ): bool {
        return $this->checkSuroccupation(
            $situationFoyer->getLogementSocialAllocation(),
            (int) $typeCompositionLogement->getCompositionLogementNombrePersonnes(),
            $typeCompositionLogement->getCompositionLogementNbPieces(),
            $typeCompositionLogement->getCompositionLogementSuperficie(),
        );
    }

    private function checkSuroccupation(
        ?string $isAllocataire,
        int $nbOccupants,
        int $nbPieces,
        ?float $superficie,
    ): bool {
        $suroccupation = false;
        if ('oui' === $isAllocataire) {
            if (null === $superficie) {
                return $suroccupation;
            }
            if (1 === $nbOccupants && $superficie < $this::ALLOCATAIRE_SUPERFICIE_1_OCCUPANT) {
                $suroccupation = true;
            } elseif (2 === $nbOccupants && $superficie < $this::ALLOCATAIRE_SUPERFICIE_2_OCCUPANTS) {
                $suroccupation = true;
            } elseif ($nbOccupants > 2) {
                $superficieNecessaire = $this::ALLOCATAIRE_SUPERFICIE_BASE +
                (($nbOccupants - 2) * $this::ALLOCATAIRE_SUPERFICIE_PER_OCCUPANT);

                if ($superficie < $superficieNecessaire) {
                    $suroccupation = true;
                }
            }
            if ($suroccupation) {
                $this->slug = 'desordres_type_composition_logement_suroccupation_allocataire';
            }
        } else {
            if ($nbPieces < $nbOccupants / $this::NON_ALLOCATAIRE_MIN_PIECES_PER_OCCUPANT) {
                $suroccupation = true;
                $this->slug = 'desordres_type_composition_logement_suroccupation_non_allocataire';
            }
        }

        return $suroccupation;
    }

        public function getSlug(): ?string
        {
            return $this->slug;
        }
}
