<?php

namespace App\Dto\Request\Signalement;

use Symfony\Component\Validator\Constraints as Assert;

class CompositionLogementRequest
{
    public function __construct(
        #[Assert\NotBlank(message: 'Merci de définir le type de logement.')]
        private readonly ?string $type = null,
        #[Assert\NotBlank(message: 'Merci de préciser le type de logement autre.', groups: ['TYPE_LOGEMENT_AUTRE'])]
        private readonly ?string $typeLogementNatureAutrePrecision = null,
        #[Assert\NotBlank(['message' => 'Merci de définir si il y a plusieurs pièces dans le logement'])]
        private readonly ?string $typeCompositionLogement = null,
        #[Assert\NotBlank(message: 'Merci de saisir la superficie du logement.', groups: ['LOCATAIRE', 'BAILLEUR_OCCUPANT'])]
        #[Assert\Positive(message: 'Merci de saisir une information numérique dans le champs de superficie.')]
        private readonly ?string $superficie = null,
        #[Assert\NotBlank(message: 'Merci de définir la hauteur du logement.', groups: ['LOCATAIRE', 'BAILLEUR_OCCUPANT', 'BAILLEUR', 'TIERS_PARTICULIER', 'TIERS_PRO', 'SERVICE_SECOURS'])]
        private readonly ?string $compositionLogementHauteur = null,
        private readonly ?string $compositionLogementNbPieces = null,
        private readonly ?string $nombreEtages = null,
        private readonly ?string $typeLogementRdc = null,
        private readonly ?string $typeLogementDernierEtage = null,
        private readonly ?string $typeLogementSousCombleSansFenetre = null,
        private readonly ?string $typeLogementSousSolSansFenetre = null,
        #[Assert\NotBlank(message: 'Merci de définir si une pièce fait plus de 9m².', groups: ['LOCATAIRE', 'BAILLEUR_OCCUPANT', 'BAILLEUR', 'TIERS_PARTICULIER'])]
        private readonly ?string $typeLogementCommoditesPieceAVivre9m = null,
        #[Assert\NotBlank(message: 'Merci de définir si il y a une cuisine.', groups: ['LOCATAIRE', 'BAILLEUR_OCCUPANT', 'BAILLEUR', 'TIERS_PARTICULIER', 'TIERS_PRO', 'SERVICE_SECOURS'])]
        private readonly ?string $typeLogementCommoditesCuisine = null,
        private readonly ?string $typeLogementCommoditesCuisineCollective = null,
        #[Assert\NotBlank(message: 'Merci de définir si il y a une salle de bain.', groups: ['LOCATAIRE', 'BAILLEUR_OCCUPANT', 'BAILLEUR', 'TIERS_PARTICULIER', 'TIERS_PRO', 'SERVICE_SECOURS'])]
        private readonly ?string $typeLogementCommoditesSalleDeBain = null,
        private readonly ?string $typeLogementCommoditesSalleDeBainCollective = null,
        #[Assert\NotBlank(message: 'Merci de définir si il y a des WC.', groups: ['LOCATAIRE', 'BAILLEUR_OCCUPANT', 'BAILLEUR', 'TIERS_PARTICULIER', 'TIERS_PRO', 'SERVICE_SECOURS'])]
        private readonly ?string $typeLogementCommoditesWc = null,
        private readonly ?string $typeLogementCommoditesWcCollective = null,
        private readonly ?string $typeLogementCommoditesWcCuisine = null,
    ) {
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function getTypeLogementNatureAutrePrecision(): ?string
    {
        return $this->typeLogementNatureAutrePrecision;
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

    public function getNombreEtages(): ?string
    {
        return $this->nombreEtages;
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
