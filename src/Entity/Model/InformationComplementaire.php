<?php

namespace App\Entity\Model;

class InformationComplementaire
{
    public function __construct(
        private ?string $informationsComplementairesSituationOccupantsBeneficiaireRsa = null,
        private ?string $informationsComplementairesSituationOccupantsBeneficiaireFsl = null,
        private ?string $informationsComplementairesSituationOccupantsDateNaissance = null,
        private ?string $informationsComplementairesLogementMontantLoyer = null,
        private ?string $informationsComplementairesLogementNombreEtages = null,
        private ?string $informationsComplementairesLogementAnneeConstruction = null,
    ) {
    }

    public function getInformationsComplementairesSituationOccupantsBeneficiaireRsa(): ?string
    {
        return $this->informationsComplementairesSituationOccupantsBeneficiaireRsa;
    }

    public function setInformationsComplementairesSituationOccupantsBeneficiaireRsa(?string $informationsComplementairesSituationOccupantsBeneficiaireRsa): self
    {
        $this->informationsComplementairesSituationOccupantsBeneficiaireRsa = $informationsComplementairesSituationOccupantsBeneficiaireRsa;

        return $this;
    }

    public function getInformationsComplementairesSituationOccupantsBeneficiaireFsl(): ?string
    {
        return $this->informationsComplementairesSituationOccupantsBeneficiaireFsl;
    }

    public function setInformationsComplementairesSituationOccupantsBeneficiaireFsl(?string $informationsComplementairesSituationOccupantsBeneficiaireFsl): self
    {
        $this->informationsComplementairesSituationOccupantsBeneficiaireFsl = $informationsComplementairesSituationOccupantsBeneficiaireFsl;

        return $this;
    }

    public function getInformationsComplementairesSituationOccupantsDateNaissance(): ?string
    {
        return $this->informationsComplementairesSituationOccupantsDateNaissance;
    }

    public function setInformationsComplementairesSituationOccupantsDateNaissance(?string $informationsComplementairesSituationOccupantsDateNaissance): self
    {
        $this->informationsComplementairesSituationOccupantsDateNaissance = $informationsComplementairesSituationOccupantsDateNaissance;

        return $this;
    }

    public function getInformationsComplementairesLogementMontantLoyer(): ?string
    {
        return $this->informationsComplementairesLogementMontantLoyer;
    }

    public function setInformationsComplementairesLogementMontantLoyer(?string $informationsComplementairesLogementMontantLoyer): self
    {
        $this->informationsComplementairesLogementMontantLoyer = $informationsComplementairesLogementMontantLoyer;

        return $this;
    }

    public function getInformationsComplementairesLogementNombreEtages(): ?string
    {
        return $this->informationsComplementairesLogementNombreEtages;
    }

    public function setInformationsComplementairesLogementNombreEtages(?string $informationsComplementairesLogementNombreEtages): self
    {
        $this->informationsComplementairesLogementNombreEtages = $informationsComplementairesLogementNombreEtages;

        return $this;
    }

    public function getInformationsComplementairesLogementAnneeConstruction(): ?string
    {
        return $this->informationsComplementairesLogementAnneeConstruction;
    }

    public function setInformationsComplementairesLogementAnneeConstruction(?string $informationsComplementairesLogementAnneeConstruction): self
    {
        $this->informationsComplementairesLogementAnneeConstruction = $informationsComplementairesLogementAnneeConstruction;

        return $this;
    }

    public function toArray(): array
    {
        return [
            'informations_complementaires_situation_occupants_beneficiaire_rsa' => $this->informationsComplementairesSituationOccupantsBeneficiaireRsa,
            'informations_complementaires_situation_occupants_beneficiaire_fsl' => $this->informationsComplementairesSituationOccupantsBeneficiaireFsl,
            'informations_complementaires_situation_occupants_date_naissance' => $this->informationsComplementairesSituationOccupantsDateNaissance,
            'informations_complementaires_logement_montant_loyer' => $this->informationsComplementairesLogementMontantLoyer,
            'informations_complementaires_logement_nombre_etages' => $this->informationsComplementairesLogementNombreEtages,
            'informations_complementaires_logement_annee_construction' => $this->informationsComplementairesLogementAnneeConstruction,
        ];
    }
}
