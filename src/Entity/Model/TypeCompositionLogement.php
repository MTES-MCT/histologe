<?php

namespace App\Entity\Model;

class TypeCompositionLogement
{
    public function __construct(
        private ?string $typeLogementNature = null,
        private ?string $typeLogementNatureAutrePrecision = null,
        private ?string $typeLogementRdc = null,
        private ?string $typeLogementDernierEtage = null,
        private ?string $typeLogementSousSolSansFenetre = null,
        private ?string $typeLogementSousCombleSansFenetre = null,
        private ?string $typeLogementCommoditesCuisine = null,
        private ?string $typeLogementCommoditesCuisineCollective = null,
        private ?string $typeLogementCommoditesSalleDeBain = null,
        private ?string $typeLogementCommoditesSalleDeBainCollective = null,
        private ?string $typeLogementCommoditesWc = null,
        private ?string $typeLogementCommoditesWcCollective = null,
        private ?string $typeLogementCommoditesWcCuisine = null,
        private ?string $compositionLogementPieceUnique = null,
        private ?string $compositionLogementSuperficie = null,
        private ?string $compositionLogementHauteur = null,
        private ?string $compositionLogementNbPieces = null,
        private ?string $compositionLogementNombrePersonnes = null,
        private ?string $compositionLogementEnfants = null,
        private ?string $typeLogementCommoditesPieceAVivre9m = null,
        private ?string $bailDpeBail = null,
        private ?string $bailDpeDpe = null,
        private ?string $bailDpeEtatDesLieux = null,
        private ?string $bailDpeDateEmmenagement = null,
        private ?string $desordresLogementChauffageDetailsDpeConsoFinale = null,
        private ?string $desordresLogementChauffageDetailsDpeConso = null,
        private ?string $desordresLogementChauffageDetailsDpeAnnee = null,
        private ?string $desordresLogementChauffageDetailsDpeConsoVide = null,
    ) {
    }

    public function getTypeLogementNature(): ?string
    {
        return $this->typeLogementNature;
    }

    public function setTypeLogementNature(?string $typeLogementNature): self
    {
        $this->typeLogementNature = $typeLogementNature;

        return $this;
    }

    public function getTypeLogementNatureAutrePrecision(): ?string
    {
        return $this->typeLogementNatureAutrePrecision;
    }

    public function setTypeLogementNatureAutrePrecision(?string $typeLogementNatureAutrePrecision): self
    {
        $this->typeLogementNatureAutrePrecision = $typeLogementNatureAutrePrecision;

        return $this;
    }

    public function getTypeLogementRdc(): ?string
    {
        return $this->typeLogementRdc;
    }

    public function setTypeLogementRdc(?string $typeLogementRdc): self
    {
        $this->typeLogementRdc = $typeLogementRdc;

        return $this;
    }

    public function getTypeLogementDernierEtage(): ?string
    {
        return $this->typeLogementDernierEtage;
    }

    public function setTypeLogementDernierEtage(?string $typeLogementDernierEtage): self
    {
        $this->typeLogementDernierEtage = $typeLogementDernierEtage;

        return $this;
    }

    public function getTypeLogementSousSolSansFenetre(): ?string
    {
        return $this->typeLogementSousSolSansFenetre;
    }

    public function setTypeLogementSousSolSansFenetre(?string $typeLogementSousSolSansFenetre): self
    {
        $this->typeLogementSousSolSansFenetre = $typeLogementSousSolSansFenetre;

        return $this;
    }

    public function getTypeLogementSousCombleSansFenetre(): ?string
    {
        return $this->typeLogementSousCombleSansFenetre;
    }

    public function setTypeLogementSousCombleSansFenetre(?string $typeLogementSousCombleSansFenetre): self
    {
        $this->typeLogementSousCombleSansFenetre = $typeLogementSousCombleSansFenetre;

        return $this;
    }

    public function getTypeLogementCommoditesCuisine(): ?string
    {
        return $this->typeLogementCommoditesCuisine;
    }

    public function setTypeLogementCommoditesCuisine(?string $typeLogementCommoditesCuisine): self
    {
        $this->typeLogementCommoditesCuisine = $typeLogementCommoditesCuisine;

        return $this;
    }

    public function getTypeLogementCommoditesCuisineCollective(): ?string
    {
        return $this->typeLogementCommoditesCuisineCollective;
    }

    public function setTypeLogementCommoditesCuisineCollective(?string $typeLogementCommoditesCuisineCollective): self
    {
        $this->typeLogementCommoditesCuisineCollective = $typeLogementCommoditesCuisineCollective;

        return $this;
    }

    public function getTypeLogementCommoditesSalleDeBain(): ?string
    {
        return $this->typeLogementCommoditesSalleDeBain;
    }

    public function setTypeLogementCommoditesSalleDeBain(?string $typeLogementCommoditesSalleDeBain): self
    {
        $this->typeLogementCommoditesSalleDeBain = $typeLogementCommoditesSalleDeBain;

        return $this;
    }

    public function getTypeLogementCommoditesSalleDeBainCollective(): ?string
    {
        return $this->typeLogementCommoditesSalleDeBainCollective;
    }

    public function setTypeLogementCommoditesSalleDeBainCollective(?string $typeLogementCommoditesSalleDeBainCollective): self
    {
        $this->typeLogementCommoditesSalleDeBainCollective = $typeLogementCommoditesSalleDeBainCollective;

        return $this;
    }

    public function getTypeLogementCommoditesWc(): ?string
    {
        return $this->typeLogementCommoditesWc;
    }

    public function setTypeLogementCommoditesWc(?string $typeLogementCommoditesWc): self
    {
        $this->typeLogementCommoditesWc = $typeLogementCommoditesWc;

        return $this;
    }

    public function getTypeLogementCommoditesWcCollective(): ?string
    {
        return $this->typeLogementCommoditesWcCollective;
    }

    public function setTypeLogementCommoditesWcCollective(?string $typeLogementCommoditesWcCollective): self
    {
        $this->typeLogementCommoditesWcCollective = $typeLogementCommoditesWcCollective;

        return $this;
    }

    public function getTypeLogementCommoditesWcCuisine(): ?string
    {
        return $this->typeLogementCommoditesWcCuisine;
    }

    public function setTypeLogementCommoditesWcCuisine(?string $typeLogementCommoditesWcCuisine): self
    {
        $this->typeLogementCommoditesWcCuisine = $typeLogementCommoditesWcCuisine;

        return $this;
    }

    public function getCompositionLogementPieceUnique(bool $raw = true): ?string
    {
        if (!$raw) {
            return match ($this->compositionLogementPieceUnique) {
                'piece_unique' => 'Pièce unique',
                'plusieurs_pieces' => 'Plusieurs pièces',
                default => $this->compositionLogementPieceUnique
            };
        }

        return $this->compositionLogementPieceUnique;
    }

    public function setCompositionLogementPieceUnique(?string $compositionLogementPieceUnique): self
    {
        $this->compositionLogementPieceUnique = $compositionLogementPieceUnique;

        return $this;
    }

    public function getCompositionLogementSuperficie(): ?string
    {
        return $this->compositionLogementSuperficie;
    }

    public function setCompositionLogementSuperficie(?string $compositionLogementSuperficie): self
    {
        $this->compositionLogementSuperficie = $compositionLogementSuperficie;

        return $this;
    }

    public function getCompositionLogementHauteur(bool $raw = true): ?string
    {
        return (!$raw && 'nsp' === $this->compositionLogementHauteur) ? 'Ne sait pas' : $this->compositionLogementHauteur;
    }

    public function setCompositionLogementHauteur(?string $compositionLogementHauteur): self
    {
        $this->compositionLogementHauteur = $compositionLogementHauteur;

        return $this;
    }

    public function getCompositionLogementNbPieces(): ?string
    {
        return $this->compositionLogementNbPieces;
    }

    public function setCompositionLogementNbPieces(?string $compositionLogementNbPieces): self
    {
        $this->compositionLogementNbPieces = $compositionLogementNbPieces;

        return $this;
    }

    public function getCompositionLogementNombrePersonnes(): ?string
    {
        return $this->compositionLogementNombrePersonnes;
    }

    public function setCompositionLogementNombrePersonnes(?string $compositionLogementNombrePersonnes): self
    {
        $this->compositionLogementNombrePersonnes = $compositionLogementNombrePersonnes;

        return $this;
    }

    public function getCompositionLogementEnfants(): ?string
    {
        return $this->compositionLogementEnfants;
    }

    public function setCompositionLogementEnfants(?string $compositionLogementEnfants): self
    {
        $this->compositionLogementEnfants = $compositionLogementEnfants;

        return $this;
    }

    public function getBailDpeBail(bool $raw = true): ?string
    {
        return (!$raw && 'nsp' === $this->bailDpeBail) ? 'Ne sait pas' : $this->bailDpeBail;
    }

    public function setBailDpeBail(?string $bailDpeBail): self
    {
        $this->bailDpeBail = $bailDpeBail;

        return $this;
    }

    public function getBailDpeDpe(bool $raw = true): ?string
    {
        return (!$raw && 'nsp' === $this->bailDpeDpe) ? 'Ne sait pas' : $this->bailDpeDpe;
    }

    public function setBailDpeDpe(?string $bailDpeDpe): self
    {
        $this->bailDpeDpe = $bailDpeDpe;

        return $this;
    }

    public function getBailDpeEtatDesLieux(bool $raw = true): ?string
    {
        return (!$raw && 'nsp' === $this->bailDpeBail) ? 'Ne sait pas' : $this->bailDpeBail;
    }

    public function setBailDpeEtatDesLieux(?string $bailDpeEtatDesLieux): self
    {
        $this->bailDpeEtatDesLieux = $bailDpeEtatDesLieux;

        return $this;
    }

    public function getBailDpeDateEmmenagement(): ?string
    {
        return $this->bailDpeDateEmmenagement;
    }

    public function setBailDpeDateEmmenagement(?string $bailDpeDateEmmenagement): self
    {
        $this->bailDpeDateEmmenagement = $bailDpeDateEmmenagement;

        return $this;
    }

    public function getTypeLogementCommoditesPieceAVivre9m(bool $raw = true): ?string
    {
        return (!$raw && 'nsp' === $this->typeLogementCommoditesPieceAVivre9m) ? 'Ne sait pas' : $this->typeLogementCommoditesPieceAVivre9m;
    }

    public function setTypeLogementCommoditesPieceAVivre9m(?string $typeLogementCommoditesPieceAVivre9m): self
    {
        $this->typeLogementCommoditesPieceAVivre9m = $typeLogementCommoditesPieceAVivre9m;

        return $this;
    }

    public function getDesordresLogementChauffageDetailsDpeConsoFinale(): ?string
    {
        return $this->desordresLogementChauffageDetailsDpeConsoFinale;
    }

    public function setDesordresLogementChauffageDetailsDpeConsoFinale($desordresLogementChauffageDetailsDpeConsoFinale): self
    {
        $this->desordresLogementChauffageDetailsDpeConsoFinale = $desordresLogementChauffageDetailsDpeConsoFinale;

        return $this;
    }

    public function getDesordresLogementChauffageDetailsDpeConso(): ?string
    {
        return $this->desordresLogementChauffageDetailsDpeConso;
    }

    public function setDesordresLogementChauffageDetailsDpeConso($desordresLogementChauffageDetailsDpeConso): self
    {
        $this->desordresLogementChauffageDetailsDpeConso = $desordresLogementChauffageDetailsDpeConso;

        return $this;
    }

    public function getDesordresLogementChauffageDetailsDpeAnnee(): ?string
    {
        return $this->desordresLogementChauffageDetailsDpeAnnee;
    }

    public function setDesordresLogementChauffageDetailsDpeAnnee($desordresLogementChauffageDetailsDpeAnnee): self
    {
        $this->desordresLogementChauffageDetailsDpeAnnee = $desordresLogementChauffageDetailsDpeAnnee;

        return $this;
    }

    public function getDesordresLogementChauffageDetailsDpeConsoVide(): ?string
    {
        return $this->desordresLogementChauffageDetailsDpeConsoVide;
    }

    public function setDesordresLogementChauffageDetailsDpeConsoVide($desordresLogementChauffageDetailsDpeConsoVide): self
    {
        $this->desordresLogementChauffageDetailsDpeConsoVide = $desordresLogementChauffageDetailsDpeConsoVide;

        return $this;
    }

    public function toArray(): array
    {
        return [
            'type_logement_nature' => $this->typeLogementNature,
            'type_logement_nature_autre_precision' => $this->typeLogementNatureAutrePrecision,
            'type_logement_rdc' => $this->typeLogementRdc,
            'type_logement_dernier_etage' => $this->typeLogementDernierEtage,
            'type_logement_sous_sol_sans_fenetre' => $this->typeLogementSousSolSansFenetre,
            'type_logement_sous_comble_sans_fenetre' => $this->typeLogementSousCombleSansFenetre,
            'type_logement_commodites_piece_a_vivre_9m' => $this->typeLogementCommoditesPieceAVivre9m,
            'type_logement_commodites_cuisine' => $this->typeLogementCommoditesCuisine,
            'type_logement_commodites_cuisine_collective' => $this->typeLogementCommoditesCuisineCollective,
            'type_logement_commodites_salle_de_bain' => $this->typeLogementCommoditesSalleDeBain,
            'type_logement_commodites_salle_de_bain_collective' => $this->typeLogementCommoditesSalleDeBainCollective,
            'type_logement_commodites_wc' => $this->typeLogementCommoditesWc,
            'type_logement_commodites_wc_collective' => $this->typeLogementCommoditesWcCollective,
            'type_logement_commodites_wc_cuisine' => $this->typeLogementCommoditesWcCuisine,
            'composition_logement_piece_unique' => $this->compositionLogementPieceUnique,
            'composition_logement_superficie' => $this->compositionLogementSuperficie,
            'composition_logement_hauteur' => $this->compositionLogementHauteur,
            'composition_logement_nb_pieces' => $this->compositionLogementNbPieces,
            'composition_logement_nombre_personnes' => $this->compositionLogementNombrePersonnes,
            'composition_logement_enfants' => $this->compositionLogementEnfants,
            'bail_dpe_bail' => $this->bailDpeBail,
            'bail_dpe_dpe' => $this->bailDpeDpe,
            'bail_dpe_etat_des_lieux' => $this->bailDpeEtatDesLieux,
            'bail_dpe_date_emmenagement' => $this->bailDpeDateEmmenagement,
            'desordres_logement_chauffage_details_dpe_conso_finale' => $this->desordresLogementChauffageDetailsDpeConsoFinale,
            'desordres_logement_chauffage_details_dpe_conso' => $this->desordresLogementChauffageDetailsDpeConso,
            'desordres_logement_chauffage_details_dpe_annee' => $this->desordresLogementChauffageDetailsDpeAnnee,
            'desordres_logement_chauffage_details_dpe_conso_vide' => $this->desordresLogementChauffageDetailsDpeConsoVide,
        ];
    }
}
