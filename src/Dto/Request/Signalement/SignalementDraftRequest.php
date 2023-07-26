<?php

namespace App\Dto\Request\Signalement;

use Symfony\Component\Serializer\Annotation\SerializedName;

class SignalementDraftRequest
{
    public function __construct(
        private readonly ?string $adresseLogementNumero = null,
        private readonly ?string $adresseLogementCodePostal = null,
        private readonly ?string $adresseLogementCommune = null,
        private readonly ?string $adresseLogementInsee = null,
        private readonly ?float $adresseLogementGeolocLat = null,
        private readonly ?float $adresseLogementGeolocLng = null,
        private readonly ?string $adresseLogementComplementAdresseEscalier = null,
        private readonly ?string $adresseLogementComplementAdresseEtage = null,
        private readonly ?string $adresseLogementComplementAdresseNumeroAppartement = null,
        private readonly ?string $signalementConcerneProfil = null,
        private readonly ?string $signalementConcerneProfilDetailOccupant = null,
        private readonly ?string $signalementConcerneProfilDetailTiers = null,
        private readonly ?string $signalementConcerneProfilDetailBailleurProprietaire = null,
        private readonly ?string $signalementConcerneProfilDetailBailleurBailleur = null,
        private readonly ?string $signalementConcerneLogementSocialServiceSecours = null,
        private readonly ?string $signalementConcerneLogementSocialAutreTiers = null,
    ) {
    }

    public function getAdresseLogementNumero(): ?string
    {
        return $this->adresseLogementNumero;
    }

    public function getAdresseLogementCodePostal(): ?string
    {
        return $this->adresseLogementCodePostal;
    }

    public function getAdresseLogementCommune(): ?string
    {
        return $this->adresseLogementCommune;
    }

    public function getAdresseLogementInsee(): ?string
    {
        return $this->adresseLogementInsee;
    }

    public function getAdresseLogementGeolocLat(): ?float
    {
        return $this->adresseLogementGeolocLat;
    }

    public function getAdresseLogementGeolocLng(): ?float
    {
        return $this->adresseLogementGeolocLng;
    }

    public function getAdresseLogementComplementAdresseEscalier(): ?string
    {
        return $this->adresseLogementComplementAdresseEscalier;
    }

    public function getAdresseLogementComplementAdresseEtage(): ?string
    {
        return $this->adresseLogementComplementAdresseEtage;
    }

    public function getAdresseLogementComplementAdresseNumeroAppartement(): ?string
    {
        return $this->adresseLogementComplementAdresseNumeroAppartement;
    }

    public function getSignalementConcerneProfil(): ?string
    {
        return $this->signalementConcerneProfil;
    }

    public function getSignalementConcerneProfilDetailOccupant(): ?string
    {
        return $this->signalementConcerneProfilDetailOccupant;
    }

    /**
     * @return string|null
     */
    public function getSignalementConcerneProfilDetailTiers(): ?string
    {
        return $this->signalementConcerneProfilDetailTiers;
    }

    public function getSignalementConcerneProfilDetailBailleurProprietaire(): ?string
    {
        return $this->signalementConcerneProfilDetailBailleurProprietaire;
    }

    public function getSignalementConcerneProfilDetailBailleurBailleur(): ?string
    {
        return $this->signalementConcerneProfilDetailBailleurBailleur;
    }

    public function getSignalementConcerneLogementSocialServiceSecours(): ?string
    {
        return $this->signalementConcerneLogementSocialServiceSecours;
    }

    public function getSignalementConcerneLogementSocialAutreTiers(): ?string
    {
        return $this->signalementConcerneLogementSocialAutreTiers;
    }
}
