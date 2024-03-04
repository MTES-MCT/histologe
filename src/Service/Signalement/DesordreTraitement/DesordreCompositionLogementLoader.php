<?php

namespace App\Service\Signalement\DesordreTraitement;

use App\Entity\DesordreCategorie;
use App\Entity\Model\TypeCompositionLogement;
use App\Entity\Signalement;
use App\Repository\DesordreCritereRepository;
use App\Repository\DesordrePrecisionRepository;

class DesordreCompositionLogementLoader
{
    private Signalement $signalement;

    public function __construct(
        private readonly DesordrePrecisionRepository $desordrePrecisionRepository,
        private readonly DesordreCritereRepository $desordreCritereRepository,
    ) {
    }

    public function load(
        Signalement $signalement,
        TypeCompositionLogement $typeCompositionLogement,
    ): void {
        $this->signalement = $signalement;
        if ('oui' === $typeCompositionLogement->getTypeLogementSousCombleSansFenetre()) {
            $this->addDesordreCriterePrecisionBySlugs(
                'desordres_type_composition_logement_sous_combles',
                'desordres_type_composition_logement_sous_combles'
            );
        } else {
            $this->removeDesordreCriterePrecisionBySlugs(
                'desordres_type_composition_logement_sous_combles',
                'desordres_type_composition_logement_sous_combles'
            );
        }

        if ('oui' === $typeCompositionLogement->getTypeLogementSousSolSansFenetre()) {
            $this->addDesordreCriterePrecisionBySlugs(
                'desordres_type_composition_logement_sous_sol',
                'desordres_type_composition_logement_sous_sol'
            );
        } else {
            $this->removeDesordreCriterePrecisionBySlugs(
                'desordres_type_composition_logement_sous_sol',
                'desordres_type_composition_logement_sous_sol'
            );
        }

        if ('piece_unique' === $typeCompositionLogement->getCompositionLogementPieceUnique()) {
            if (null !== $typeCompositionLogement->getCompositionLogementSuperficie()
                && $typeCompositionLogement->getCompositionLogementSuperficie() < 9
            ) {
                $this->addDesordreCriterePrecisionBySlugs(
                    'desordres_type_composition_logement_piece_unique',
                    'desordres_type_composition_logement_piece_unique_superficie'
                );
            } else {
                $this->removeDesordreCriterePrecisionBySlugs(
                    'desordres_type_composition_logement_piece_unique',
                    'desordres_type_composition_logement_piece_unique_superficie'
                );
            }
            if ('non' === $typeCompositionLogement->getCompositionLogementHauteur()) {
                $this->addDesordreCriterePrecisionBySlugs(
                    'desordres_type_composition_logement_piece_unique',
                    'desordres_type_composition_logement_piece_unique_hauteur'
                );
            } else {
                $this->removeDesordreCriterePrecisionBySlugs(
                    'desordres_type_composition_logement_piece_unique',
                    'desordres_type_composition_logement_piece_unique_hauteur'
                );
            }
        } else {
            if ('non' === $typeCompositionLogement->getTypeLogementCommoditesPieceAVivre9m()) {
                $this->addDesordreCriterePrecisionBySlugs(
                    'desordres_type_composition_logement_plusieurs_pieces',
                    'desordres_type_composition_logement_plusieurs_pieces_aucune_piece_9'
                );
            } else {
                $this->removeDesordreCriterePrecisionBySlugs(
                    'desordres_type_composition_logement_plusieurs_pieces',
                    'desordres_type_composition_logement_plusieurs_pieces_aucune_piece_9'
                );
            }
            if ('non' === $typeCompositionLogement->getCompositionLogementHauteur()) {
                $this->addDesordreCriterePrecisionBySlugs(
                    'desordres_type_composition_logement_plusieurs_pieces',
                    'desordres_type_composition_logement_plusieurs_pieces_hauteur'
                );
            } else {
                $this->removeDesordreCriterePrecisionBySlugs(
                    'desordres_type_composition_logement_plusieurs_pieces',
                    'desordres_type_composition_logement_plusieurs_pieces_hauteur'
                );
            }
        }

        if ('non' === $typeCompositionLogement->getTypeLogementCommoditesCuisine()) {
            if ('non' === $typeCompositionLogement->getTypeLogementCommoditesCuisineCollective()) {
                $this->addDesordreCriterePrecisionBySlugs(
                    'desordres_type_composition_logement_cuisine',
                    'desordres_type_composition_logement_cuisine_collective_non'
                );
            } else {
                $this->addDesordreCriterePrecisionBySlugs(
                    'desordres_type_composition_logement_cuisine',
                    'desordres_type_composition_logement_cuisine_collective_oui'
                );
            }
        } else {
            $this->removeDesordreCriterePrecisionBySlugs(
                'desordres_type_composition_logement_cuisine',
                'desordres_type_composition_logement_cuisine_collective_non'
            );
            $this->removeDesordreCriterePrecisionBySlugs(
                'desordres_type_composition_logement_cuisine',
                'desordres_type_composition_logement_cuisine_collective_oui'
            );
        }

        if ('non' === $typeCompositionLogement->getTypeLogementCommoditesSalleDeBain()) {
            if ('non' === $typeCompositionLogement->getTypeLogementCommoditesSalleDeBainCollective()) {
                $this->addDesordreCriterePrecisionBySlugs(
                    'desordres_type_composition_logement_douche',
                    'desordres_type_composition_logement_douche_collective_non'
                );
            } else {
                $this->addDesordreCriterePrecisionBySlugs(
                    'desordres_type_composition_logement_douche',
                    'desordres_type_composition_logement_douche_collective_oui'
                );
            }
        } else {
            $this->removeDesordreCriterePrecisionBySlugs(
                'desordres_type_composition_logement_douche',
                'desordres_type_composition_logement_douche_collective_non'
            );
            $this->removeDesordreCriterePrecisionBySlugs(
                'desordres_type_composition_logement_douche',
                'desordres_type_composition_logement_douche_collective_oui'
            );
        }

        if ('non' === $typeCompositionLogement->getTypeLogementCommoditesWc()) {
            if ('non' === $typeCompositionLogement->getTypeLogementCommoditesWcCollective()) {
                $this->addDesordreCriterePrecisionBySlugs(
                    'desordres_type_composition_logement_wc',
                    'desordres_type_composition_logement_wc_collectif_non'
                );
            } else {
                $this->addDesordreCriterePrecisionBySlugs(
                    'desordres_type_composition_logement_wc',
                    'desordres_type_composition_logement_wc_collectif_oui'
                );
            }
        } else {
            $this->removeDesordreCriterePrecisionBySlugs(
                'desordres_type_composition_logement_wc',
                'desordres_type_composition_logement_wc_collectif_non'
            );
            $this->removeDesordreCriterePrecisionBySlugs(
                'desordres_type_composition_logement_wc',
                'desordres_type_composition_logement_wc_collectif_oui'
            );
            if ('oui' === $typeCompositionLogement->getTypeLogementCommoditesWcCuisine()) {
                $this->addDesordreCriterePrecisionBySlugs(
                    'desordres_type_composition_logement_wc',
                    'desordres_type_composition_logement_wc_cuisine_ensemble'
                );
            } else {
                $this->removeDesordreCriterePrecisionBySlugs(
                    'desordres_type_composition_logement_wc',
                    'desordres_type_composition_logement_wc_cuisine_ensemble'
                );
            }
        }
    }

    private function addDesordreCriterePrecisionBySlugs(string $slugCritere, string $slugPrecision): void
    {
        $critereToLink = $this->desordreCritereRepository->findOneBy(['slugCritere' => $slugCritere]);
        if (null !== $critereToLink
            && !$this->signalement->hasDesordreCritere($critereToLink)) {
            $this->signalement->addDesordreCritere($critereToLink);
        }
        $precisionToLink = $this->desordrePrecisionRepository->findOneBy(['desordrePrecisionSlug' => $slugPrecision]);
        if (null !== $precisionToLink
            && !$this->signalement->hasDesordrePrecision($precisionToLink)) {
            $this->signalement->addDesordrePrecision($precisionToLink);
        }
        if (!$this->signalement->hasDesordreCategorie($critereToLink->getDesordreCategorie())) {
            $this->signalement->addDesordreCategory($critereToLink->getDesordreCategorie());
        }
    }

    private function removeDesordreCriterePrecisionBySlugs(string $slugCritere, string $slugPrecision): void
    {
        $critereToLink = $this->desordreCritereRepository->findOneBy(['slugCritere' => $slugCritere]);
        if (null !== $critereToLink
            && $this->signalement->hasDesordreCritere($critereToLink)) {
            $this->signalement->removeDesordreCritere($critereToLink);
        }
        $precisionToLink = $this->desordrePrecisionRepository->findOneBy(['desordrePrecisionSlug' => $slugPrecision]);
        if (null !== $precisionToLink
            && $this->signalement->hasDesordrePrecision($precisionToLink)) {
            $this->signalement->removeDesordrePrecision($precisionToLink);
        }

        if (null !== $critereToLink && $this->signalement->hasDesordreCategorie($critereToLink->getDesordreCategorie())
            && !$this->hasDesordreCritereInCategorie($this->signalement, $critereToLink->getDesordreCategorie())) {
            $this->signalement->removeDesordreCategory($critereToLink->getDesordreCategorie());
        }
    }

    private function hasDesordreCritereInCategorie(Signalement $signalement, DesordreCategorie $desordreCategorie): bool
    {
        $hasDesordreCritere = false;
        foreach ($signalement->getDesordreCriteres() as $critere) {
            if ($critere->getDesordreCategorie() === $desordreCategorie) {
                $hasDesordreCritere = true;
            }
        }

        return $hasDesordreCritere;
    }
}
