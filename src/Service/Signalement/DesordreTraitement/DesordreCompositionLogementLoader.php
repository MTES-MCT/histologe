<?php

namespace App\Service\Signalement\DesordreTraitement;

use App\Entity\Model\TypeCompositionLogement;
use App\Entity\Signalement;
use App\Repository\DesordrePrecisionRepository;

class DesordreCompositionLogementLoader
{
    private Signalement $signalement;

    public function __construct(
        private readonly DesordrePrecisionRepository $desordrePrecisionRepository,
    ) {
    }

    public function load(
        Signalement $signalement,
        TypeCompositionLogement $typeCompositionLogement,
    ): void {
        $this->signalement = $signalement;
        if (!$this->signalement->isV2()) {
            return;
        }
        if ('oui' === $typeCompositionLogement->getTypeLogementSousCombleSansFenetre()) {
            $this->addDesordrePrecisionBySlug('desordres_type_composition_logement_sous_combles');
        } else {
            $this->removeDesordrePrecisionBySlug('desordres_type_composition_logement_sous_combles');
        }

        if ('oui' === $typeCompositionLogement->getTypeLogementSousSolSansFenetre()) {
            $this->addDesordrePrecisionBySlug('desordres_type_composition_logement_sous_sol');
        } else {
            $this->removeDesordrePrecisionBySlug('desordres_type_composition_logement_sous_sol');
        }

        if ('piece_unique' === $typeCompositionLogement->getCompositionLogementPieceUnique()) {
            if (null !== $typeCompositionLogement->getCompositionLogementSuperficie()
                && $typeCompositionLogement->getCompositionLogementSuperficie() < 9
            ) {
                $this->addDesordrePrecisionBySlug('desordres_type_composition_logement_piece_unique_superficie');
            } else {
                $this->removeDesordrePrecisionBySlug('desordres_type_composition_logement_piece_unique_superficie');
            }
        } else {
            if ('non' === $typeCompositionLogement->getTypeLogementCommoditesPieceAVivre9m()) {
                $this->addDesordrePrecisionBySlug('desordres_type_composition_logement_plusieurs_pieces_aucune_piece_9');
            } else {
                $this->removeDesordrePrecisionBySlug('desordres_type_composition_logement_plusieurs_pieces_aucune_piece_9');
            }
        }

        if ('non' === $typeCompositionLogement->getTypeLogementCommoditesCuisine()) {
            if ('non' === $typeCompositionLogement->getTypeLogementCommoditesCuisineCollective()) {
                $this->addDesordrePrecisionBySlug('desordres_type_composition_logement_cuisine_collective_non');
            } else {
                $this->addDesordrePrecisionBySlug('desordres_type_composition_logement_cuisine_collective_oui');
            }
        } else {
            $this->removeDesordrePrecisionBySlug('desordres_type_composition_logement_cuisine_collective_non');
            $this->removeDesordrePrecisionBySlug('desordres_type_composition_logement_cuisine_collective_oui');
        }

        if ('non' === $typeCompositionLogement->getTypeLogementCommoditesSalleDeBain()) {
            if ('non' === $typeCompositionLogement->getTypeLogementCommoditesSalleDeBainCollective()) {
                $this->addDesordrePrecisionBySlug('desordres_type_composition_logement_douche_collective_non');
            } else {
                $this->addDesordrePrecisionBySlug('desordres_type_composition_logement_douche_collective_oui');
            }
        } else {
            $this->removeDesordrePrecisionBySlug('desordres_type_composition_logement_douche_collective_non');
            $this->removeDesordrePrecisionBySlug('desordres_type_composition_logement_douche_collective_oui');
        }

        if ('non' === $typeCompositionLogement->getTypeLogementCommoditesWc()) {
            if ('non' === $typeCompositionLogement->getTypeLogementCommoditesWcCollective()) {
                $this->addDesordrePrecisionBySlug('desordres_type_composition_logement_wc_collectif_non');
            } else {
                $this->addDesordrePrecisionBySlug('desordres_type_composition_logement_wc_collectif_oui');
            }
        } else {
            $this->removeDesordrePrecisionBySlug('desordres_type_composition_logement_wc_collectif_non');
            $this->removeDesordrePrecisionBySlug('desordres_type_composition_logement_wc_collectif_oui');
            if ('oui' === $typeCompositionLogement->getTypeLogementCommoditesWcCuisine()) {
                $this->addDesordrePrecisionBySlug('desordres_type_composition_logement_wc_cuisine_ensemble');
            } else {
                $this->removeDesordrePrecisionBySlug('desordres_type_composition_logement_wc_cuisine_ensemble');
            }
        }
    }

    private function addDesordrePrecisionBySlug(string $slugPrecision): void
    {
        $precisionToLink = $this->desordrePrecisionRepository->findOneBy(['desordrePrecisionSlug' => $slugPrecision]);
        if (null !== $precisionToLink
            && !$this->signalement->hasDesordrePrecision($precisionToLink)) {
            $this->signalement->addDesordrePrecision($precisionToLink);
        }
    }

    private function removeDesordrePrecisionBySlug(string $slugPrecision): void
    {
        $precisionToLink = $this->desordrePrecisionRepository->findOneBy(['desordrePrecisionSlug' => $slugPrecision]);
        if (null !== $precisionToLink
            && $this->signalement->hasDesordrePrecision($precisionToLink)) {
            $this->signalement->removeDesordrePrecision($precisionToLink);
        }
    }
}
