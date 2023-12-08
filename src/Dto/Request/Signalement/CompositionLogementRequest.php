<?php

namespace App\Dto\Request\Signalement;

use Symfony\Component\Validator\Constraints as Assert;

class CompositionLogementRequest
{
    public function __construct(
        #[Assert\NotBlank(['message' => 'Veuillez sélectionner le nombre de pièces du logement'])]
        private readonly ?string $typeCompositionLogement = null,
        private readonly ?string $superficie = null,
        private readonly ?string $compositionLogementHauteur = null,
        private readonly ?string $compositionLogementNbPieces = null,
        private readonly ?string $typeLogementRdc = null,
        private readonly ?string $typeLogementDernierEtage = null,
        private readonly ?string $typeLogementSousCombleSansFenetre = null,
        private readonly ?string $typeLogementSousSolSansFenetre = null,
        private readonly ?string $typeLogementCommoditesPieceAVivre9m = null,
        private readonly ?string $typeLogementCommoditesCuisine = null,
        private readonly ?string $typeLogementCommoditesCuisineCollective = null,
        private readonly ?string $typeLogementCommoditesSalleDeBain = null,
        private readonly ?string $typeLogementCommoditesSalleDeBainCollective = null,
        private readonly ?string $typeLogementCommoditesWc = null,
        private readonly ?string $typeLogementCommoditesWcCollective = null,
        private readonly ?string $typeLogementCommoditesWcCuisine = null,
    ) {
    }

    public function getTypeCompositionLogement(): ?string
    {
        return $this->typeCompositionLogement;
    }

    public function getSuperficie(): ?string
    {
        return $this->superficie;
    }

    public function getCompositionLogementHauteur(): ?string
    {
        return $this->compositionLogementHauteur;
    }

    public function getCompositionLogementNbPieces(): ?string
    {
        return $this->compositionLogementNbPieces;
    }

    public function getTypeLogementRdc(): ?string
    {
        return $this->typeLogementRdc;
    }

    public function getTypeLogementDernierEtage(): ?string
    {
        return $this->typeLogementDernierEtage;
    }

    public function getTypeLogementSousCombleSansFenetre(): ?string
    {
        return $this->typeLogementSousCombleSansFenetre;
    }

    public function getTypeLogementSousSolSansFenetre(): ?string
    {
        return $this->typeLogementSousSolSansFenetre;
    }

    public function getTypeLogementCommoditesPieceAVivre9m(): ?string
    {
        return $this->typeLogementCommoditesPieceAVivre9m;
    }

    public function getTypeLogementCommoditesCuisine(): ?string
    {
        return $this->typeLogementCommoditesCuisine;
    }

    public function getTypeLogementCommoditesCuisineCollective(): ?string
    {
        return $this->typeLogementCommoditesCuisineCollective;
    }

    public function getTypeLogementCommoditesSalleDeBain(): ?string
    {
        return $this->typeLogementCommoditesSalleDeBain;
    }

    public function getTypeLogementCommoditesSalleDeBainCollective(): ?string
    {
        return $this->typeLogementCommoditesSalleDeBainCollective;
    }

    public function getTypeLogementCommoditesWc(): ?string
    {
        return $this->typeLogementCommoditesWc;
    }

    public function getTypeLogementCommoditesWcCollective(): ?string
    {
        return $this->typeLogementCommoditesWcCollective;
    }

    public function getTypeLogementCommoditesWcCuisine(): ?string
    {
        return $this->typeLogementCommoditesWcCuisine;
    }
}
