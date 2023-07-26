<?php

namespace App\Dto\Request\Signalement;

class SignalementDraftRequest
{
    private ?string $adresseLogementAdresse = null;
    private ?string $adresseLogementAdresseDetailNumero = null;
    private ?string $adresseLogementAdresseDetailCodePostal = null;
    private ?string $adresseLogementAdresseDetailCommune = null;
    private ?string $adresseLogementAdresseDetailInsee = null;
    private ?float $adresseLogementAdresseDetailGeolocLat = null;
    private ?float $adresseLogementAdresseDetailGeolocLng = null;
    private ?string $adresseLogementComplementAdresseEscalier = null;
    private ?string $adresseLogementComplementAdresseEtage = null;
    private ?string $adresseLogementComplementAdresseNumeroAppartement = null;
    private ?string $signalementConcerneProfil = null;
    private ?string $signalementConcerneProfilDetailOccupant = null;
    private ?string $signalementConcerneProfilDetailTiers = null;
    private ?string $signalementConcerneProfilDetailBailleurProprietaire = null;
    private ?string $signalementConcerneProfilDetailBailleurBailleur = null;
    private ?string $signalementConcerneLogementSocialServiceSecours = null;
    private ?string $signalementConcerneLogementSocialAutreTiers = null;
    private ?string $vosCoordonneesOccupantCivilite = null;
    private ?string $vosCoordonneesOccupantNom = null;
    private ?string $vosCoordonneesOccupantPrenom = null;
    private ?string $vosCoordonneesOccupantEmail = null;
    private ?string $vosCoordonneesOccupantTel = null;
    private ?string $coordonneesBailleurNom = null;
    private ?string $coordonneesBailleurPrenom = null;
    private ?string $coordonneesBailleurEmail = null;
    private ?string $coordonneesBailleurTel = null;
    private ?string $coordonneesBailleurAdresse = null;
    private ?string $coordonneesBailleurAdresseDetailNumero = null;
    private ?string $coordonneesBailleurAdresseDetailCodePostal = null;
    private ?string $coordonneesBailleurAdresseCommune = null;
    private ?string $zoneConcerneeZone = null;
    private ?string $typeLogementNature = null;
    private ?string $compositionLogementPieceUnique = null;
    private ?string $compositionLogementSuperficie = null;
    private ?string $compositionLogementNbPieces = null;
    private ?array $typeLogementPiecesAVivreSuperficiePiece = null;
    private ?array $typeLogementPiecesAVivreHauteurPiece = null;
    private ?string $typeLogementCommoditesCuisine = null;
    private ?string $typeLogementCommoditesCuisineCollective = null;
    private ?string $typeLogementCommoditesCuisineHauteurPlafond = null;
    private ?string $typeLogementCommoditesSalleDeBain = null;
    private ?string $typeLogementCommoditesSalleDeBainCollective = null;
    private ?string $typeLogementCommoditesSalleDeBainHauteurPlafond = null;
    private ?string $typeLogementCommoditesWc = null;
    private ?string $typeLogementCommoditesWcCollective = null;
    private ?string $typeLogementCommoditesWcHauteurPlafond = null;
    private ?string $typeLogementCommoditesWcCuisine = null;
    private ?string $bailDpeDateEmmenagement = null;
    private ?string $bailDpeBail = null;
    private ?string $bailDpeBailUpload = null;
    private ?string $bailDpeEtatDesLieux = null;
    private ?string $bailDpeDpe = null;
    private ?string $logementSocialDemandeRelogement = null;
    private ?string $logementSocialAllocation = null;
    private ?string $logementSocialAllocationCaisse = null;
    private ?string $logement_social_date_naissance = null;
    private ?string $logementSocialMontantAllocation = null;
    private ?string $travailleurSocialQuitteLogement = null;
    private ?string $travailleurSocialAccompagnement = null;
    private ?string $infoProcedureBailleurPrevenu = null;
    private ?string $infoProcedureAssuranceContactee = null;
    private ?string $infoProcedureDepartApresTravaux = null;
    private ?string $info_procedure_utilisation_service = null;

    public function getAdresseLogementAdresse(): ?string
    {
        return $this->adresseLogementAdresse;
    }

    public function setAdresseLogementAdresse(?string $adresseLogementAdresse): self
    {
        $this->adresseLogementAdresse = $adresseLogementAdresse;

        return $this;
    }

    public function getAdresseLogementAdresseDetailNumero(): ?string
    {
        return $this->adresseLogementAdresseDetailNumero;
    }

    public function setAdresseLogementAdresseDetailNumero(?string $adresseLogementAdresseDetailNumero): self
    {
        $this->adresseLogementAdresseDetailNumero = $adresseLogementAdresseDetailNumero;

        return $this;
    }

    public function getAdresseLogementAdresseDetailCodePostal(): ?string
    {
        return $this->adresseLogementAdresseDetailCodePostal;
    }

    public function setAdresseLogementAdresseDetailCodePostal(?string $adresseLogementAdresseDetailCodePostal): self
    {
        $this->adresseLogementAdresseDetailCodePostal = $adresseLogementAdresseDetailCodePostal;

        return $this;
    }

    public function getAdresseLogementAdresseDetailCommune(): ?string
    {
        return $this->adresseLogementAdresseDetailCommune;
    }

    public function setAdresseLogementAdresseDetailCommune(?string $adresseLogementAdresseDetailCommune): self
    {
        $this->adresseLogementAdresseDetailCommune = $adresseLogementAdresseDetailCommune;

        return $this;
    }

    public function getAdresseLogementAdresseDetailInsee(): ?string
    {
        return $this->adresseLogementAdresseDetailInsee;
    }

    public function setAdresseLogementAdresseDetailInsee(?string $adresseLogementAdresseDetailInsee): self
    {
        $this->adresseLogementAdresseDetailInsee = $adresseLogementAdresseDetailInsee;

        return $this;
    }

    public function getAdresseLogementAdresseDetailGeolocLat(): ?float
    {
        return $this->adresseLogementAdresseDetailGeolocLat;
    }

    public function setAdresseLogementAdresseDetailGeolocLat(?float $adresseLogementAdresseDetailGeolocLat): self
    {
        $this->adresseLogementAdresseDetailGeolocLat = $adresseLogementAdresseDetailGeolocLat;

        return $this;
    }

    public function getAdresseLogementAdresseDetailGeolocLng(): ?float
    {
        return $this->adresseLogementAdresseDetailGeolocLng;
    }

    public function setAdresseLogementAdresseDetailGeolocLng(?float $adresseLogementAdresseDetailGeolocLng): self
    {
        $this->adresseLogementAdresseDetailGeolocLng = $adresseLogementAdresseDetailGeolocLng;

        return $this;
    }

    public function getAdresseLogementComplementAdresseEscalier(): ?string
    {
        return $this->adresseLogementComplementAdresseEscalier;
    }

    public function setAdresseLogementComplementAdresseEscalier(?string $adresseLogementComplementAdresseEscalier): self
    {
        $this->adresseLogementComplementAdresseEscalier = $adresseLogementComplementAdresseEscalier;

        return $this;
    }

    public function getAdresseLogementComplementAdresseEtage(): ?string
    {
        return $this->adresseLogementComplementAdresseEtage;
    }

    public function setAdresseLogementComplementAdresseEtage(?string $adresseLogementComplementAdresseEtage): self
    {
        $this->adresseLogementComplementAdresseEtage = $adresseLogementComplementAdresseEtage;

        return $this;
    }

    public function getAdresseLogementComplementAdresseNumeroAppartement(): ?string
    {
        return $this->adresseLogementComplementAdresseNumeroAppartement;
    }

    public function setAdresseLogementComplementAdresseNumeroAppartement(?string $adresseLogementComplementAdresseNumeroAppartement): self
    {
        $this->adresseLogementComplementAdresseNumeroAppartement = $adresseLogementComplementAdresseNumeroAppartement;

        return $this;
    }

    public function getSignalementConcerneProfil(): ?string
    {
        return $this->signalementConcerneProfil;
    }

    public function setSignalementConcerneProfil(?string $signalementConcerneProfil): self
    {
        $this->signalementConcerneProfil = $signalementConcerneProfil;

        return $this;
    }

    public function getSignalementConcerneProfilDetailOccupant(): ?string
    {
        return $this->signalementConcerneProfilDetailOccupant;
    }

    public function setSignalementConcerneProfilDetailOccupant(?string $signalementConcerneProfilDetailOccupant): self
    {
        $this->signalementConcerneProfilDetailOccupant = $signalementConcerneProfilDetailOccupant;

        return $this;
    }

    public function getSignalementConcerneProfilDetailTiers(): ?string
    {
        return $this->signalementConcerneProfilDetailTiers;
    }

    public function setSignalementConcerneProfilDetailTiers(?string $signalementConcerneProfilDetailTiers): self
    {
        $this->signalementConcerneProfilDetailTiers = $signalementConcerneProfilDetailTiers;

        return $this;
    }

    public function getSignalementConcerneProfilDetailBailleurProprietaire(): ?string
    {
        return $this->signalementConcerneProfilDetailBailleurProprietaire;
    }

    public function setSignalementConcerneProfilDetailBailleurProprietaire(?string $signalementConcerneProfilDetailBailleurProprietaire): self
    {
        $this->signalementConcerneProfilDetailBailleurProprietaire = $signalementConcerneProfilDetailBailleurProprietaire;

        return $this;
    }

    public function getSignalementConcerneProfilDetailBailleurBailleur(): ?string
    {
        return $this->signalementConcerneProfilDetailBailleurBailleur;
    }

    public function setSignalementConcerneProfilDetailBailleurBailleur(?string $signalementConcerneProfilDetailBailleurBailleur): self
    {
        $this->signalementConcerneProfilDetailBailleurBailleur = $signalementConcerneProfilDetailBailleurBailleur;

        return $this;
    }

    public function getSignalementConcerneLogementSocialServiceSecours(): ?string
    {
        return $this->signalementConcerneLogementSocialServiceSecours;
    }

    public function setSignalementConcerneLogementSocialServiceSecours(?string $signalementConcerneLogementSocialServiceSecours): self
    {
        $this->signalementConcerneLogementSocialServiceSecours = $signalementConcerneLogementSocialServiceSecours;

        return $this;
    }

    public function getSignalementConcerneLogementSocialAutreTiers(): ?string
    {
        return $this->signalementConcerneLogementSocialAutreTiers;
    }

    public function setSignalementConcerneLogementSocialAutreTiers(?string $signalementConcerneLogementSocialAutreTiers): self
    {
        $this->signalementConcerneLogementSocialAutreTiers = $signalementConcerneLogementSocialAutreTiers;

        return $this;
    }

    public function getVosCoordonneesOccupantCivilite(): ?string
    {
        return $this->vosCoordonneesOccupantCivilite;
    }

    public function setVosCoordonneesOccupantCivilite(?string $vosCoordonneesOccupantCivilite): self
    {
        $this->vosCoordonneesOccupantCivilite = $vosCoordonneesOccupantCivilite;

        return $this;
    }

    public function getVosCoordonneesOccupantNom(): ?string
    {
        return $this->vosCoordonneesOccupantNom;
    }

    public function setVosCoordonneesOccupantNom(?string $vosCoordonneesOccupantNom): self
    {
        $this->vosCoordonneesOccupantNom = $vosCoordonneesOccupantNom;

        return $this;
    }

    public function getVosCoordonneesOccupantPrenom(): ?string
    {
        return $this->vosCoordonneesOccupantPrenom;
    }

    public function setVosCoordonneesOccupantPrenom(?string $vosCoordonneesOccupantPrenom): self
    {
        $this->vosCoordonneesOccupantPrenom = $vosCoordonneesOccupantPrenom;

        return $this;
    }

    public function getVosCoordonneesOccupantEmail(): ?string
    {
        return $this->vosCoordonneesOccupantEmail;
    }

    public function setVosCoordonneesOccupantEmail(?string $vosCoordonneesOccupantEmail): self
    {
        $this->vosCoordonneesOccupantEmail = $vosCoordonneesOccupantEmail;

        return $this;
    }

    public function getVosCoordonneesOccupantTel(): ?string
    {
        return $this->vosCoordonneesOccupantTel;
    }

    public function setVosCoordonneesOccupantTel(?string $vosCoordonneesOccupantTel): self
    {
        $this->vosCoordonneesOccupantTel = $vosCoordonneesOccupantTel;

        return $this;
    }

    public function getCoordonneesBailleurNom(): ?string
    {
        return $this->coordonneesBailleurNom;
    }

    public function setCoordonneesBailleurNom(?string $coordonneesBailleurNom): self
    {
        $this->coordonneesBailleurNom = $coordonneesBailleurNom;

        return $this;
    }

    public function getCoordonneesBailleurPrenom(): ?string
    {
        return $this->coordonneesBailleurPrenom;
    }

    public function setCoordonneesBailleurPrenom(?string $coordonneesBailleurPrenom): self
    {
        $this->coordonneesBailleurPrenom = $coordonneesBailleurPrenom;

        return $this;
    }

    public function getCoordonneesBailleurEmail(): ?string
    {
        return $this->coordonneesBailleurEmail;
    }

    public function setCoordonneesBailleurEmail(?string $coordonneesBailleurEmail): self
    {
        $this->coordonneesBailleurEmail = $coordonneesBailleurEmail;

        return $this;
    }

    public function getCoordonneesBailleurTel(): ?string
    {
        return $this->coordonneesBailleurTel;
    }

    public function setCoordonneesBailleurTel(?string $coordonneesBailleurTel): self
    {
        $this->coordonneesBailleurTel = $coordonneesBailleurTel;

        return $this;
    }

    public function getCoordonneesBailleurAdresse(): ?string
    {
        return $this->coordonneesBailleurAdresse;
    }

    public function setCoordonneesBailleurAdresse(?string $coordonneesBailleurAdresse): self
    {
        $this->coordonneesBailleurAdresse = $coordonneesBailleurAdresse;

        return $this;
    }

    public function getCoordonneesBailleurAdresseDetailNumero(): ?string
    {
        return $this->coordonneesBailleurAdresseDetailNumero;
    }

    public function setCoordonneesBailleurAdresseDetailNumero(?string $coordonneesBailleurAdresseDetailNumero): self
    {
        $this->coordonneesBailleurAdresseDetailNumero = $coordonneesBailleurAdresseDetailNumero;

        return $this;
    }

    public function getCoordonneesBailleurAdresseDetailCodePostal(): ?string
    {
        return $this->coordonneesBailleurAdresseDetailCodePostal;
    }

    public function setCoordonneesBailleurAdresseDetailCodePostal(?string $coordonneesBailleurAdresseDetailCodePostal): self
    {
        $this->coordonneesBailleurAdresseDetailCodePostal = $coordonneesBailleurAdresseDetailCodePostal;

        return $this;
    }

    public function getCoordonneesBailleurAdresseCommune(): ?string
    {
        return $this->coordonneesBailleurAdresseCommune;
    }

    public function setCoordonneesBailleurAdresseCommune(?string $coordonneesBailleurAdresseCommune): self
    {
        $this->coordonneesBailleurAdresseCommune = $coordonneesBailleurAdresseCommune;

        return $this;
    }

    public function getZoneConcerneeZone(): ?string
    {
        return $this->zoneConcerneeZone;
    }

    public function setZoneConcerneeZone(?string $zoneConcerneeZone): self
    {
        $this->zoneConcerneeZone = $zoneConcerneeZone;

        return $this;
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

    public function getCompositionLogementPieceUnique(): ?string
    {
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

    public function getCompositionLogementNbPieces(): ?string
    {
        return $this->compositionLogementNbPieces;
    }

    public function setCompositionLogementNbPieces(?string $compositionLogementNbPieces): self
    {
        $this->compositionLogementNbPieces = $compositionLogementNbPieces;

        return $this;
    }

    public function getTypeLogementPiecesAVivreSuperficiePiece(): ?array
    {
        return $this->typeLogementPiecesAVivreSuperficiePiece;
    }

    public function setTypeLogementPiecesAVivreSuperficiePiece(?array $typeLogementPiecesAVivreSuperficiePiece): self
    {
        $this->typeLogementPiecesAVivreSuperficiePiece = $typeLogementPiecesAVivreSuperficiePiece;

        return $this;
    }

    public function getTypeLogementPiecesAVivreHauteurPiece(): ?array
    {
        return $this->typeLogementPiecesAVivreHauteurPiece;
    }

    public function setTypeLogementPiecesAVivreHauteurPiece(?array $typeLogementPiecesAVivreHauteurPiece): self
    {
        $this->typeLogementPiecesAVivreHauteurPiece = $typeLogementPiecesAVivreHauteurPiece;

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

    public function getTypeLogementCommoditesCuisineHauteurPlafond(): ?string
    {
        return $this->typeLogementCommoditesCuisineHauteurPlafond;
    }

    public function setTypeLogementCommoditesCuisineHauteurPlafond(?string $typeLogementCommoditesCuisineHauteurPlafond): self
    {
        $this->typeLogementCommoditesCuisineHauteurPlafond = $typeLogementCommoditesCuisineHauteurPlafond;

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

    public function getTypeLogementCommoditesSalleDeBainHauteurPlafond(): ?string
    {
        return $this->typeLogementCommoditesSalleDeBainHauteurPlafond;
    }

    public function setTypeLogementCommoditesSalleDeBainHauteurPlafond(?string $typeLogementCommoditesSalleDeBainHauteurPlafond): self
    {
        $this->typeLogementCommoditesSalleDeBainHauteurPlafond = $typeLogementCommoditesSalleDeBainHauteurPlafond;

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

    public function getTypeLogementCommoditesWcHauteurPlafond(): ?string
    {
        return $this->typeLogementCommoditesWcHauteurPlafond;
    }

    public function setTypeLogementCommoditesWcHauteurPlafond(?string $typeLogementCommoditesWcHauteurPlafond): self
    {
        $this->typeLogementCommoditesWcHauteurPlafond = $typeLogementCommoditesWcHauteurPlafond;

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

    public function getBailDpeDateEmmenagement(): ?string
    {
        return $this->bailDpeDateEmmenagement;
    }

    public function setBailDpeDateEmmenagement(?string $bailDpeDateEmmenagement): self
    {
        $this->bailDpeDateEmmenagement = $bailDpeDateEmmenagement;

        return $this;
    }

    public function getBailDpeBail(): ?string
    {
        return $this->bailDpeBail;
    }

    public function setBailDpeBail(?string $bailDpeBail): self
    {
        $this->bailDpeBail = $bailDpeBail;

        return $this;
    }

    public function getBailDpeBailUpload(): ?string
    {
        return $this->bailDpeBailUpload;
    }

    public function setBailDpeBailUpload(?string $bailDpeBailUpload): self
    {
        $this->bailDpeBailUpload = $bailDpeBailUpload;

        return $this;
    }

    public function getBailDpeEtatDesLieux(): ?string
    {
        return $this->bailDpeEtatDesLieux;
    }

    public function setBailDpeEtatDesLieux(?string $bailDpeEtatDesLieux): self
    {
        $this->bailDpeEtatDesLieux = $bailDpeEtatDesLieux;

        return $this;
    }

    public function getBailDpeDpe(): ?string
    {
        return $this->bailDpeDpe;
    }

    public function setBailDpeDpe(?string $bailDpeDpe): self
    {
        $this->bailDpeDpe = $bailDpeDpe;

        return $this;
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
        return $this->logement_social_date_naissance;
    }

    public function setLogementSocialDateNaissance(?string $logement_social_date_naissance): self
    {
        $this->logement_social_date_naissance = $logement_social_date_naissance;

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

    public function getTravailleurSocialQuitteLogement(): ?string
    {
        return $this->travailleurSocialQuitteLogement;
    }

    public function setTravailleurSocialQuitteLogement(?string $travailleurSocialQuitteLogement): self
    {
        $this->travailleurSocialQuitteLogement = $travailleurSocialQuitteLogement;

        return $this;
    }

    public function getTravailleurSocialAccompagnement(): ?string
    {
        return $this->travailleurSocialAccompagnement;
    }

    public function setTravailleurSocialAccompagnement(?string $travailleurSocialAccompagnement): self
    {
        $this->travailleurSocialAccompagnement = $travailleurSocialAccompagnement;

        return $this;
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

    public function getInfoProcedureAssuranceContactee(): ?string
    {
        return $this->infoProcedureAssuranceContactee;
    }

    public function setInfoProcedureAssuranceContactee(?string $infoProcedureAssuranceContactee): self
    {
        $this->infoProcedureAssuranceContactee = $infoProcedureAssuranceContactee;

        return $this;
    }

    public function getInfoProcedureDepartApresTravaux(): ?string
    {
        return $this->infoProcedureDepartApresTravaux;
    }

    public function setInfoProcedureDepartApresTravaux(?string $infoProcedureDepartApresTravaux): self
    {
        $this->infoProcedureDepartApresTravaux = $infoProcedureDepartApresTravaux;

        return $this;
    }

    public function getInfoProcedureUtilisationService(): ?string
    {
        return $this->info_procedure_utilisation_service;
    }

    public function setInfoProcedureUtilisationService(?string $info_procedure_utilisation_service): self
    {
        $this->info_procedure_utilisation_service = $info_procedure_utilisation_service;

        return $this;
    }
}
