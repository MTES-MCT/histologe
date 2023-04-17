<?php

namespace App\Messenger\Message;

final class DossierMessageSCHS
{
    private ?string $url = null;

    private ?string $token = null;

    private ?int $signalementId = null;

    private ?int $partnerId = null;

    private ?string $reference = null;

    private ?string $prenomUsager = null;

    private ?string $nomUsager = null;

    private ?string $mailUsager = null;

    private ?string $telephoneUsager = null;

    private ?string $numeroAdresseSignalement = null;

    private ?string $adresseSignalement = null;

    private ?string $etageSignalement = null;

    private ?string $numeroAppartementSignalement = null;

    private ?string $codepostaleSignalement = null;

    private ?string $villeSignalement = null;

    private ?float $latitudeSignalement = null;

    private ?float $longitudeSignalement = null;

    private ?string $dateOuverture = null;

    private ?string $dossierCommentaire = null;

    private ?string $piecesJointesObservation = null;

    private array $piecesJointes = [];

    public function getPartnerId(): ?int
    {
        return $this->partnerId;
    }

    public function setPartnerId(?string $partnerId): self
    {
        $this->partnerId = $partnerId;

        return $this;
    }

    public function getReference(): ?string
    {
        return $this->reference;
    }

    public function setReference(?string $reference): self
    {
        $this->reference = $reference;

        return $this;
    }

    public function getPrenomUsager(): ?string
    {
        return $this->prenomUsager;
    }

    public function setPrenomUsager(?string $prenomUsager): self
    {
        $this->prenomUsager = $prenomUsager;

        return $this;
    }

    public function getNomUsager(): ?string
    {
        return $this->nomUsager;
    }

    public function setNomUsager(?string $nomUsager): self
    {
        $this->nomUsager = $nomUsager;

        return $this;
    }

    public function getMailUsager(): ?string
    {
        return $this->mailUsager;
    }

    public function setMailUsager(?string $mailUsager): self
    {
        $this->mailUsager = $mailUsager;

        return $this;
    }

    public function getTelephoneUsager(): ?string
    {
        return $this->telephoneUsager;
    }

    public function setTelephoneUsager(?string $telephoneUsager): self
    {
        $this->telephoneUsager = $telephoneUsager;

        return $this;
    }

    public function getNumeroAdresseSignalement(): ?string
    {
        return $this->numeroAdresseSignalement;
    }

    public function setNumeroAdresseSignalement(?string $numeroAdresseSignalement): self
    {
        $this->numeroAdresseSignalement = $numeroAdresseSignalement;

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

    public function getEtageSignalement(): ?string
    {
        return $this->etageSignalement;
    }

    public function setEtageSignalement(?string $etageSignalement): self
    {
        $this->etageSignalement = $etageSignalement;

        return $this;
    }

    public function getNumeroAppartementSignalement(): ?string
    {
        return $this->numeroAppartementSignalement;
    }

    public function setNumeroAppartementSignalement(?string $numeroAppartementSignalement): self
    {
        $this->numeroAppartementSignalement = $numeroAppartementSignalement;

        return $this;
    }

    public function getCodepostaleSignalement(): ?string
    {
        return $this->codepostaleSignalement;
    }

    public function setCodepostaleSignalement(?string $codepostaleSignalement): self
    {
        $this->codepostaleSignalement = $codepostaleSignalement;

        return $this;
    }

    public function getVilleSignalement(): ?string
    {
        return $this->villeSignalement;
    }

    public function setVilleSignalement(?string $villeSignalement): self
    {
        $this->villeSignalement = $villeSignalement;

        return $this;
    }

    public function getLatitudeSignalement(): ?float
    {
        return $this->latitudeSignalement;
    }

    public function setLatitudeSignalement(?float $latitudeSignalement): self
    {
        $this->latitudeSignalement = $latitudeSignalement;

        return $this;
    }

    public function getLongitudeSignalement(): ?float
    {
        return $this->longitudeSignalement;
    }

    public function setLongitudeSignalement(?float $longitudeSignalement): self
    {
        $this->longitudeSignalement = $longitudeSignalement;

        return $this;
    }

    public function getDateOuverture(): ?string
    {
        return $this->dateOuverture;
    }

    public function setDateOuverture(?string $dateOuverture): self
    {
        $this->dateOuverture = $dateOuverture;

        return $this;
    }

    public function getDossierCommentaire(): ?string
    {
        return $this->dossierCommentaire;
    }

    public function setDossierCommentaire(?string $dossierCommentaire): self
    {
        $this->dossierCommentaire = $dossierCommentaire;

        return $this;
    }

    public function getPiecesJointesObservation(): ?string
    {
        return $this->piecesJointesObservation;
    }

    public function setPiecesJointesObservation(?string $piecesJointesObservation): self
    {
        $this->piecesJointesObservation = $piecesJointesObservation;

        return $this;
    }

    public function getPiecesJointes(): array
    {
        return $this->piecesJointes;
    }

    public function setPiecesJointes(array $piecesJointes): self
    {
        $this->piecesJointes = $piecesJointes;

        return $this;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function setUrl(?string $url): self
    {
        $this->url = $url;

        return $this;
    }

    public function getToken(): ?string
    {
        return $this->token;
    }

    public function setToken(?string $token): self
    {
        $this->token = $token;

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
}
