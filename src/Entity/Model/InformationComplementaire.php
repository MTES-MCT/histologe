<?php

namespace App\Entity\Model;

class InformationComplementaire
{
    public function __construct(
        private ?string $informationsComplementairesSituationOccupantsBeneficiaireRsa = null,
        private ?string $informationsComplementairesSituationOccupantsBeneficiaireFsl = null,
        private ?string $informationsComplementairesSituationOccupantsDateNaissance = null,
        private ?string $informationsComplementairesSituationOccupantsDemandeRelogement = null,
        private ?string $informationsComplementairesSituationOccupantsDateEmmenagement = null,
        private ?string $informationsComplementairesSituationOccupantsLoyersPayes = null,
        private ?string $informationsComplementairesSituationOccupantsPreavisDepart = null,
        private ?string $informationsComplementairesSituationBailleurDateEffetBail = null,
        private ?string $informationsComplementairesSituationBailleurBeneficiaireRsa = null,
        private ?string $informationsComplementairesSituationBailleurBeneficiaireFsl = null,
        private ?string $informationsComplementairesSituationBailleurRevenuFiscal = null,
        private ?string $informationsComplementairesSituationBailleurDateNaissance = null,
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

    public function getInformationsComplementairesSituationOccupantsDemandeRelogement(): ?string
    {
        return $this->informationsComplementairesSituationOccupantsDemandeRelogement;
    }

    public function setInformationsComplementairesSituationOccupantsDemandeRelogement(?string $informationsComplementairesSituationOccupantsDemandeRelogement): self
    {
        $this->informationsComplementairesSituationOccupantsDemandeRelogement = $informationsComplementairesSituationOccupantsDemandeRelogement;

        return $this;
    }

    public function getInformationsComplementairesSituationOccupantsDateEmmenagement(): ?string
    {
        return $this->informationsComplementairesSituationOccupantsDateEmmenagement;
    }

    public function setInformationsComplementairesSituationOccupantsDateEmmenagement(?string $informationsComplementairesSituationOccupantsDateEmmenagement): self
    {
        $this->informationsComplementairesSituationOccupantsDateEmmenagement = $informationsComplementairesSituationOccupantsDateEmmenagement;

        return $this;
    }

    public function getInformationsComplementairesSituationOccupantsLoyersPayes(): ?string
    {
        return $this->informationsComplementairesSituationOccupantsLoyersPayes;
    }

    public function setInformationsComplementairesSituationOccupantsLoyersPayes(?string $informationsComplementairesSituationOccupantsLoyersPayes): self
    {
        $this->informationsComplementairesSituationOccupantsLoyersPayes = $informationsComplementairesSituationOccupantsLoyersPayes;

        return $this;
    }

    public function getInformationsComplementairesSituationOccupantsPreavisDepart(): ?string
    {
        return $this->informationsComplementairesSituationOccupantsPreavisDepart;
    }

    public function setInformationsComplementairesSituationOccupantsPreavisDepart(?string $informationsComplementairesSituationOccupantsPreavisDepart): self
    {
        $this->informationsComplementairesSituationOccupantsPreavisDepart = $informationsComplementairesSituationOccupantsPreavisDepart;

        return $this;
    }

    public function getInformationsComplementairesSituationBailleurDateEffetBail(): ?string
    {
        return $this->informationsComplementairesSituationBailleurDateEffetBail;
    }

    public function setInformationsComplementairesSituationBailleurDateEffetBail(?string $informationsComplementairesSituationBailleurDateEffetBail): self
    {
        $this->informationsComplementairesSituationBailleurDateEffetBail = $informationsComplementairesSituationBailleurDateEffetBail;

        return $this;
    }

    public function getInformationsComplementairesSituationBailleurBeneficiaireRsa(): ?string
    {
        return $this->informationsComplementairesSituationBailleurBeneficiaireRsa;
    }

    public function setInformationsComplementairesSituationBailleurBeneficiaireRsa(?string $informationsComplementairesSituationBailleurBeneficiaireRsa): self
    {
        $this->informationsComplementairesSituationBailleurBeneficiaireRsa = $informationsComplementairesSituationBailleurBeneficiaireRsa;

        return $this;
    }

    public function getInformationsComplementairesSituationBailleurBeneficiaireFsl(): ?string
    {
        return $this->informationsComplementairesSituationBailleurBeneficiaireFsl;
    }

    public function setInformationsComplementairesSituationBailleurBeneficiaireFsl(?string $informationsComplementairesSituationBailleurBeneficiaireFsl): self
    {
        $this->informationsComplementairesSituationBailleurBeneficiaireFsl = $informationsComplementairesSituationBailleurBeneficiaireFsl;

        return $this;
    }

    public function getInformationsComplementairesSituationBailleurRevenuFiscal(): ?string
    {
        return $this->informationsComplementairesSituationBailleurRevenuFiscal;
    }

    public function setInformationsComplementairesSituationBailleurRevenuFiscal(?string $informationsComplementairesSituationBailleurRevenuFiscal): self
    {
        $this->informationsComplementairesSituationBailleurRevenuFiscal = $informationsComplementairesSituationBailleurRevenuFiscal;

        return $this;
    }

    public function getInformationsComplementairesSituationBailleurDateNaissance(): ?string
    {
        return $this->informationsComplementairesSituationBailleurDateNaissance;
    }

    public function setInformationsComplementairesSituationBailleurDateNaissance(?string $informationsComplementairesSituationBailleurDateNaissance): self
    {
        $this->informationsComplementairesSituationBailleurDateNaissance = $informationsComplementairesSituationBailleurDateNaissance;

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
            'informations_complementaires_situation_occupants_demande_relogement' => $this->informationsComplementairesSituationOccupantsDemandeRelogement,
            'informations_complementaires_situation_occupants_date_emmenagement' => $this->informationsComplementairesSituationOccupantsDateEmmenagement,
            'informations_complementaires_situation_occupants_loyers_payes' => $this->informationsComplementairesSituationOccupantsLoyersPayes,
            'informations_complementaires_situation_occupants_preavis_depart' => $this->informationsComplementairesSituationOccupantsPreavisDepart,
            'informations_complementaires_situation_bailleur_date_effet_bail' => $this->informationsComplementairesSituationBailleurDateEffetBail,
            'informations_complementaires_situation_bailleur_beneficiaire_rsa' => $this->informationsComplementairesSituationBailleurBeneficiaireRsa,
            'informations_complementaires_situation_bailleur_beneficiaire_fsl' => $this->informationsComplementairesSituationBailleurBeneficiaireFsl,
            'informations_complementaires_situation_bailleur_revenu_fiscal' => $this->informationsComplementairesSituationBailleurRevenuFiscal,
            'informations_complementaires_situation_bailleur_date_naissance' => $this->informationsComplementairesSituationBailleurDateNaissance,
            'informations_complementaires_logement_montant_loyer' => $this->informationsComplementairesLogementMontantLoyer,
            'informations_complementaires_logement_nombre_etages' => $this->informationsComplementairesLogementNombreEtages,
            'informations_complementaires_logement_annee_construction' => $this->informationsComplementairesLogementAnneeConstruction,
        ];
    }
}
