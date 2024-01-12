<?php

namespace App\Messenger\Message\Oilhi;

final class DossierMessage
{
    private ?string $signalementUrl = null;
    private ?int $signalementId = null;
    private ?int $partnerId = null;
    private ?string $uuidSignalement = null;
    private ?string $dateDepotSignalement = null;
    private ?string $dateAffectationSignalement = null;
    private ?string $courrielPartenaire = null;
    private ?string $courrielContributeurs = null;
    private ?string $adresseSignalement = null;
    private ?string $communeSignalement = null;
    private ?string $codePostalSignalement = null;
    private ?string $typeDeclarant = null;
    private ?string $telephoneDeclarant = null;
    private ?string $courrielDeclarant = null;
    private ?string $nomOccupant = null;
    private ?string $prenomOccupant = null;
    private ?string $telephoneOccupant = null;
    private ?string $courrielOccupant = null;
    private ?string $nomProprietaire = null;
    private ?string $telephoneProprietaire = null;
    private ?string $courrielProprietaire = null;
    private ?string $desordresCategorie = null;
    private ?string $desordresCritere = null;
    private ?string $desordresPrecision = null;
    private ?string $statut = null;
    private ?string $rapportVisite = null;
    private ?string $dateVisite = null;
    private ?string $operateurVisite = null;

    public function __construct()
    {
        $this->statut = '⌛️ Procédure en cours';
    }

    public function getSignalementUrl(): ?string
    {
        return $this->signalementUrl;
    }

    public function setSignalementUrl(?string $signalementUrl): self
    {
        $this->signalementUrl = $signalementUrl;

        return $this;
    }

    public function getSignalementId(): ?int
    {
        return $this->signalementId;
    }

    public function setSignalementId(?int $signalementId): self
    {
        $this->signalementId = $signalementId;

        return $this;
    }

    public function getPartnerId(): ?int
    {
        return $this->partnerId;
    }

    public function setPartnerId(?int $partnerId): self
    {
        $this->partnerId = $partnerId;

        return $this;
    }

    public function getUuidSignalement(): ?string
    {
        return $this->uuidSignalement;
    }

    public function setUuidSignalement(?string $uuidSignalement): self
    {
        $this->uuidSignalement = $uuidSignalement;

        return $this;
    }

    public function getDateDepotSignalement(): ?string
    {
        return $this->dateDepotSignalement;
    }

    public function setDateDepotSignalement(?string $dateDepotSignalement): self
    {
        $this->dateDepotSignalement = $dateDepotSignalement;

        return $this;
    }

    public function getDateAffectationSignalement(): ?string
    {
        return $this->dateAffectationSignalement;
    }

    public function setDateAffectationSignalement(?string $dateAffectationSignalement): self
    {
        $this->dateAffectationSignalement = $dateAffectationSignalement;

        return $this;
    }

    public function getCourrielPartenaire(): ?string
    {
        return $this->courrielPartenaire;
    }

    public function setCourrielPartenaire(?string $courrielPartenaire): self
    {
        $this->courrielPartenaire = $courrielPartenaire;

        return $this;
    }

    public function getCourrielContributeurs(): ?string
    {
        return $this->courrielContributeurs;
    }

    public function setCourrielContributeurs(?string $courrielContributeurs): self
    {
        $this->courrielContributeurs = $courrielContributeurs;

        return $this;
    }

    public function getAdresseSignalement(): ?string
    {
        return $this->adresseSignalement;
    }

    public function setAdresseSignalement(?string $adresseSignalement): self
    {
        $this->adresseSignalement = $adresseSignalement;

        return $this;
    }

    public function getCommuneSignalement(): ?string
    {
        return $this->communeSignalement;
    }

    public function setCommuneSignalement(?string $communeSignalement): self
    {
        $this->communeSignalement = $communeSignalement;

        return $this;
    }

    public function getCodePostalSignalement(): ?string
    {
        return $this->codePostalSignalement;
    }

    public function setCodePostalSignalement(?string $codePostalSignalement): self
    {
        $this->codePostalSignalement = $codePostalSignalement;

        return $this;
    }

    public function getTypeDeclarant(): ?string
    {
        return $this->typeDeclarant;
    }

    public function setTypeDeclarant(?string $typeDeclarant): self
    {
        $this->typeDeclarant = $typeDeclarant;

        return $this;
    }

    public function getTelephoneDeclarant(): ?string
    {
        return $this->telephoneDeclarant;
    }

    public function setTelephoneDeclarant(?string $telephoneDeclarant): self
    {
        $this->telephoneDeclarant = $telephoneDeclarant;

        return $this;
    }

    public function getCourrielDeclarant(): ?string
    {
        return $this->courrielDeclarant;
    }

    public function setCourrielDeclarant(?string $courrielDeclarant): self
    {
        $this->courrielDeclarant = $courrielDeclarant;

        return $this;
    }

    public function getNomOccupant(): ?string
    {
        return $this->nomOccupant;
    }

    public function setNomOccupant(?string $nomOccupant): self
    {
        $this->nomOccupant = $nomOccupant;

        return $this;
    }

    public function getPrenomOccupant(): ?string
    {
        return $this->prenomOccupant;
    }

    public function setPrenomOccupant(?string $prenomOccupant): self
    {
        $this->prenomOccupant = $prenomOccupant;

        return $this;
    }

    public function getTelephoneOccupant(): ?string
    {
        return $this->telephoneOccupant;
    }

    public function setTelephoneOccupant(?string $telephoneOccupant): self
    {
        $this->telephoneOccupant = $telephoneOccupant;

        return $this;
    }

    public function getCourrielOccupant(): ?string
    {
        return $this->courrielOccupant;
    }

    public function setCourrielOccupant(?string $courrielOccupant): self
    {
        $this->courrielOccupant = $courrielOccupant;

        return $this;
    }

    public function getNomProprietaire(): ?string
    {
        return $this->nomProprietaire;
    }

    public function setNomProprietaire(?string $nomProprietaire): self
    {
        $this->nomProprietaire = $nomProprietaire;

        return $this;
    }

    public function getTelephoneProprietaire(): ?string
    {
        return $this->telephoneProprietaire;
    }

    public function setTelephoneProprietaire(?string $telephoneProprietaire): self
    {
        $this->telephoneProprietaire = $telephoneProprietaire;

        return $this;
    }

    public function getCourrielProprietaire(): ?string
    {
        return $this->courrielProprietaire;
    }

    public function setCourrielProprietaire(?string $courrielProprietaire): self
    {
        $this->courrielProprietaire = $courrielProprietaire;

        return $this;
    }

    public function getDesordresCategorie(): ?string
    {
        return $this->desordresCategorie;
    }

    public function setDesordresCategorie(?string $desordresCategorie): self
    {
        $this->desordresCategorie = $desordresCategorie;

        return $this;
    }

    public function getDesordresCritere(): ?string
    {
        return $this->desordresCritere;
    }

    public function setDesordresCritere(?string $desordresCritere): self
    {
        $this->desordresCritere = $desordresCritere;

        return $this;
    }

    public function getDesordresPrecision(): ?string
    {
        return $this->desordresPrecision;
    }

    public function setDesordresPrecision(?string $desordresPrecision): self
    {
        $this->desordresPrecision = $desordresPrecision;

        return $this;
    }

    public function getStatut(): ?string
    {
        return $this->statut;
    }

    public function setStatut(?string $statut): self
    {
        $this->statut = $statut;

        return $this;
    }

    public function getRapportVisite(): ?string
    {
        return $this->rapportVisite;
    }

    public function setRapportVisite(?string $rapportVisite): self
    {
        $this->rapportVisite = $rapportVisite;

        return $this;
    }

    public function getDateVisite(): ?string
    {
        return $this->dateVisite;
    }

    public function setDateVisite(?string $dateVisite): self
    {
        $this->dateVisite = $dateVisite;

        return $this;
    }

    public function getOperateurVisite(): ?string
    {
        return $this->operateurVisite;
    }

    public function setOperateurVisite(?string $operateurVisite): self
    {
        $this->operateurVisite = $operateurVisite;

        return $this;
    }
}
