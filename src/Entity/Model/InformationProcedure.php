<?php

namespace App\Entity\Model;

class InformationProcedure
{
    public function __construct(
        private ?string $infoProcedureBailleurPrevenu = null,
        private ?string $infoProcedureAssuranceContactee = null,
        private ?string $infoProcedureDepartApresTravaux = null,
        private ?string $infoProcedureReponseAssurance = null,
        private ?bool $utilisationServiceOkPrevenirBailleur = null,
        private ?bool $utilisationServiceOkVisite = null,
        private ?bool $utilisationServiceOkDemandeLogement = null,
        private ?bool $utilisationServiceOkCgu = null,
    ) {
    }

    public function getInfoProcedureBailleurPrevenu(): ?string
    {
        return $this->infoProcedureBailleurPrevenu;
    }

    public function setInfoProcedureBailleurPrevenu(?string $infoProcedureBailleurPrevenu): self
    {
        $this->infoProcedureBailleurPrevenu = $infoProcedureBailleurPrevenu;

        return $this;
    }

    public function getInfoProcedureAssuranceContactee(bool $raw = true): ?string
    {
        if (!$raw) {
            if ('pas_assurance_logement' === $this->infoProcedureAssuranceContactee) {
                return 'Pas d\'assurance logement';
            } elseif ('nsp' === $this->infoProcedureAssuranceContactee) {
                return 'Ne sait pas';
            }
        }
        
        return $this->infoProcedureAssuranceContactee;
    }

    public function setInfoProcedureAssuranceContactee(?string $infoProcedureAssuranceContactee): self
    {
        $this->infoProcedureAssuranceContactee = $infoProcedureAssuranceContactee;

        return $this;
    }

    public function getInfoProcedureDepartApresTravaux(bool $raw = true): ?string
    {
        return (!$raw && 'nsp' === $this->infoProcedureDepartApresTravaux) ? 'Ne sait pas' : $this->infoProcedureDepartApresTravaux;
    }

    public function setInfoProcedureDepartApresTravaux(?string $infoProcedureDepartApresTravaux): self
    {
        $this->infoProcedureDepartApresTravaux = $infoProcedureDepartApresTravaux;

        return $this;
    }

    public function getInfoProcedureReponseAssurance(): ?string
    {
        return $this->infoProcedureReponseAssurance;
    }

    public function setInfoProcedureReponseAssurance(?string $infoProcedureReponseAssurance): self
    {
        $this->infoProcedureReponseAssurance = $infoProcedureReponseAssurance;

        return $this;
    }

    public function getUtilisationServiceOkPrevenirBailleur(): ?bool
    {
        return $this->utilisationServiceOkPrevenirBailleur;
    }

    public function setUtilisationServiceOkPrevenirBailleur(?bool $utilisationServiceOkPrevenirBailleur): self
    {
        $this->utilisationServiceOkPrevenirBailleur = $utilisationServiceOkPrevenirBailleur;

        return $this;
    }

    public function getUtilisationServiceOkVisite(): ?bool
    {
        return $this->utilisationServiceOkVisite;
    }

    public function setUtilisationServiceOkVisite(?bool $utilisationServiceOkVisite): self
    {
        $this->utilisationServiceOkVisite = $utilisationServiceOkVisite;

        return $this;
    }

    public function getUtilisationServiceOkDemandeLogement(): ?bool
    {
        return $this->utilisationServiceOkDemandeLogement;
    }

    public function setUtilisationServiceOkDemandeLogement(?bool $utilisationServiceOkDemandeLogement): self
    {
        $this->utilisationServiceOkDemandeLogement = $utilisationServiceOkDemandeLogement;

        return $this;
    }

    public function getUtilisationServiceOkCgu(): ?bool
    {
        return $this->utilisationServiceOkCgu;
    }

    public function setUtilisationServiceOkCgu(?bool $utilisationServiceOkCgu): self
    {
        $this->utilisationServiceOkCgu = $utilisationServiceOkCgu;

        return $this;
    }

    public function toArray(): array
    {
        return [
            'info_procedure_bailleur_prevenu' => $this->infoProcedureBailleurPrevenu,
            'info_procedure_assurance_contactee' => $this->infoProcedureAssuranceContactee,
            'info_procedure_depart_apres_travaux' => $this->infoProcedureDepartApresTravaux,
            'info_procedure_reponse_assurance' => $this->infoProcedureReponseAssurance,
            'utilisation_service_ok_prevenir_bailleur' => $this->utilisationServiceOkPrevenirBailleur,
            'utilisation_service_ok_visite' => $this->utilisationServiceOkVisite,
            'utilisation_service_ok_demande_logement' => $this->utilisationServiceOkDemandeLogement,
            'utilisation_service_ok_cgu' => $this->utilisationServiceOkCgu,
        ];
    }
}
