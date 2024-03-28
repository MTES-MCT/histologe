<?php

namespace App\Entity\Model;

class SituationFoyer
{
    public function __construct(
        private ?string $logementSocialDemandeRelogement = null,
        private ?string $logementSocialAllocation = null,
        private ?string $logementSocialAllocationCaisse = null,
        private ?string $logementSocialDateNaissance = null,
        private ?string $logementSocialMontantAllocation = null,
        private ?string $logementSocialNumeroAllocataire = null,
        private ?string $travailleurSocialQuitteLogement = null,
        private ?string $travailleurSocialPreavisDepart = null,
        private ?string $travailleurSocialAccompagnement = null,
        private ?string $travailleurSocialAccompagnementDeclarant = null
    ) {
    }

    public function getLogementSocialDemandeRelogement(): ?string
    {
        return $this->logementSocialDemandeRelogement;
    }

    public function setLogementSocialDemandeRelogement(?string $logementSocialDemandeRelogement): self
    {
        $this->logementSocialDemandeRelogement = $logementSocialDemandeRelogement;

        return $this;
    }

    public function getLogementSocialAllocation(): ?string
    {
        return $this->logementSocialAllocation;
    }

    public function setLogementSocialAllocation(?string $logementSocialAllocation): self
    {
        $this->logementSocialAllocation = $logementSocialAllocation;

        return $this;
    }

    public function getLogementSocialAllocationCaisse(): ?string
    {
        return $this->logementSocialAllocationCaisse;
    }

    public function setLogementSocialAllocationCaisse(?string $logementSocialAllocationCaisse): self
    {
        $this->logementSocialAllocationCaisse = $logementSocialAllocationCaisse;

        return $this;
    }

    public function getLogementSocialDateNaissance(): ?string
    {
        return $this->logementSocialDateNaissance;
    }

    public function setLogementSocialDateNaissance(?string $logementSocialDateNaissance): self
    {
        $this->logementSocialDateNaissance = $logementSocialDateNaissance;

        return $this;
    }

    public function getLogementSocialMontantAllocation(): ?string
    {
        return $this->logementSocialMontantAllocation;
    }

    public function setLogementSocialMontantAllocation(?string $logementSocialMontantAllocation): self
    {
        $this->logementSocialMontantAllocation = $logementSocialMontantAllocation;

        return $this;
    }

    public function getLogementSocialNumeroAllocataire(): ?string
    {
        return $this->logementSocialNumeroAllocataire;
    }

    public function setLogementSocialNumeroAllocataire(?string $logementSocialNumeroAllocataire): self
    {
        $this->logementSocialNumeroAllocataire = $logementSocialNumeroAllocataire;

        return $this;
    }

    public function getTravailleurSocialQuitteLogement(bool $raw = true): ?string
    {
        return (!$raw && 'nsp' === $this->travailleurSocialQuitteLogement)
            ? 'Ne sait pas'
            : $this->travailleurSocialQuitteLogement;
    }

    public function setTravailleurSocialQuitteLogement(?string $travailleurSocialQuitteLogement): self
    {
        $this->travailleurSocialQuitteLogement = $travailleurSocialQuitteLogement;

        return $this;
    }

    public function getTravailleurSocialPreavisDepart(bool $raw = true): ?string
    {
        return (!$raw && 'nsp' === $this->travailleurSocialPreavisDepart)
            ? 'Ne sait pas'
            : $this->travailleurSocialPreavisDepart;
    }

    public function setTravailleurSocialPreavisDepart(?string $travailleurSocialPreavisDepart): self
    {
        $this->travailleurSocialPreavisDepart = $travailleurSocialPreavisDepart;

        return $this;
    }

    public function getTravailleurSocialAccompagnement(bool $raw = true): ?string
    {
        return (!$raw && 'nsp' === $this->travailleurSocialAccompagnement)
            ? 'Ne sait pas'
            : $this->travailleurSocialAccompagnement;
    }

    public function setTravailleurSocialAccompagnement(?string $travailleurSocialAccompagnement): self
    {
        $this->travailleurSocialAccompagnement = $travailleurSocialAccompagnement;

        return $this;
    }

    public function getTravailleurSocialAccompagnementDeclarant(bool $raw = true): ?string
    {
        return (!$raw && 'nsp' === $this->travailleurSocialAccompagnementDeclarant)
            ? 'Ne sait pas'
            : $this->travailleurSocialAccompagnementDeclarant;
    }

    public function setTravailleurSocialAccompagnementDeclarant(?string $travailleurSocialAccompagnementDeclarant): self
    {
        $this->travailleurSocialAccompagnementDeclarant = $travailleurSocialAccompagnementDeclarant;

        return $this;
    }

    public function toArray(): array
    {
        return [
            'logement_social_demande_relogement' => $this->logementSocialDemandeRelogement,
            'logement_social_allocation' => $this->logementSocialAllocation,
            'logement_social_allocation_caisse' => $this->logementSocialAllocationCaisse,
            'logement_social_date_naissance' => $this->logementSocialDateNaissance,
            'logement_social_montant_allocation' => $this->logementSocialMontantAllocation,
            'logement_social_numero_allocataire' => $this->logementSocialNumeroAllocataire,
            'travailleur_social_quitte_logement' => $this->travailleurSocialQuitteLogement,
            'travailleur_social_preavis_depart' => $this->travailleurSocialPreavisDepart,
            'travailleur_social_accompagnement' => $this->travailleurSocialAccompagnement,
            'travailleur_social_accompagnement_declarant' => $this->travailleurSocialAccompagnementDeclarant,
        ];
    }
}
