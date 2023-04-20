<?php

namespace App\Messenger\Message;

use App\Entity\Enum\SISHDossierType;

final class DossierMessageSISH
{
    private ?string $url = null;
    private ?string $token = null;
    private ?int $signalementId = null;
    private ?int $partnerId = null;
    private ?string $referenceAdresse = null;
    private ?string $localisationNumero = null;
    private ?string $localisationNumeroExt = null;
    private ?string $localisationAdresse1 = null;
    private ?string $localisationAdresse2 = null;
    private ?string $localisationAdresse3 = null;
    private ?string $localisationCodePostal = null;
    private ?string $localisationVille = null;
    private ?string $localisationLocalisationInsee = null;
    private ?int $sasAdresse = null;
    private ?string $sasLogicielProvenance = null;
    private ?string $referenceDossier = null;
    private ?SISHDossierType $sasTypeDossier = null;
    private ?string $sasDateAffectation = null;
    private ?string $localisationEtage = null;
    private ?string $localisationEscalier = null;
    private ?string $localisationNumPorte = null;
    private ?string $sitOccupantNbAdultes = null;
    private ?string $sitOccupantNbEnfantsM6 = null;
    private ?string $sitOccupantNbEnfantsP6 = null;
    private ?int $sitOccupantNbOccupants = null;
    private ?string $sitOccupantNumAllocataire = null;
    private ?float $sitOccupantMontantAllocation = null;
    private ?int $sitLogementBailEncours = null;
    private ?string $sitLogementBailDateEntree = null;
    private ?int $sitLogementPreavisDepart = null;
    private ?int $sitLogementRelogement = null;
    private ?float $sitLogementSuperficie = null;
    private ?float $sitLogementMontantLoyer = null;
    private ?int $declarantNonOccupant = null;
    private ?string $logementNature = null;
    private ?string $logementType = null;
    private ?int $logementSocial = null;
    private ?string $logementAnneeConstruction = null;
    private ?string $logementTypeEnergie = null;
    private ?int $logementCollectif = null;
    private ?int $logementAvant1949 = null;
    private ?int $logementDiagST = null;
    private ?string $logementInvariant = null;
    private ?int $logementNbPieces = null;
    private ?int $logementNbChambres = null;
    private ?int $logementNbNiveaux = null;
    private ?int $proprietaireAverti = null;
    private ?string $proprietaireAvertiDate = null;
    private ?string $proprietaireAvertiMoyen = null;
    private ?float $signalementScore = null;
    private ?string $signalementOrigine = null;
    private ?string $signalementNumero = null;
    private ?string $signalementCommentaire = null;
    private ?string $signalementDate = null;
    private ?string $signalementDetails = null;
    private ?string $signalementProblemes = null;
    private ?string $piecesJointesObservation = null;
    private array $piecesJointesDocuments = [];

    private ?int $sasDossierId = null;
    private ?string $personneType = null;
    private ?string $personneNom = null;
    private ?string $personnePrenom = null;
    private ?string $personneTelephone = null;
    private ?string $personneEmail = null;
    private ?string $personneLienOccupant = null;
    private ?string $personneStructure = null;
    private ?string $personneAdresse = null;
    private ?string $personneRepresentant = null;

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

    public function getPartnerId(): ?int
    {
        return $this->partnerId;
    }

    public function setPartnerId(?int $partnerId): self
    {
        $this->partnerId = $partnerId;

        return $this;
    }

    public function getReferenceAdresse(): ?string
    {
        return $this->referenceAdresse;
    }

    public function setReferenceAdresse(?string $referenceAdresse): self
    {
        $this->referenceAdresse = $referenceAdresse;

        return $this;
    }

    public function getLocalisationNumero(): ?string
    {
        return $this->localisationNumero;
    }

    public function setLocalisationNumero(?string $localisationNumero): self
    {
        $this->localisationNumero = $localisationNumero;

        return $this;
    }

    public function getLocalisationNumeroExt(): ?string
    {
        return $this->localisationNumeroExt;
    }

    public function setLocalisationNumeroExt(?string $localisationNumeroExt): self
    {
        $this->localisationNumeroExt = $localisationNumeroExt;

        return $this;
    }

    public function getLocalisationAdresse1(): ?string
    {
        return $this->localisationAdresse1;
    }

    public function setLocalisationAdresse1(?string $localisationAdresse1): self
    {
        $this->localisationAdresse1 = $localisationAdresse1;

        return $this;
    }

    public function getLocalisationAdresse2(): ?string
    {
        return $this->localisationAdresse2;
    }

    public function setLocalisationAdresse2(?string $localisationAdresse2): self
    {
        $this->localisationAdresse2 = $localisationAdresse2;

        return $this;
    }

    public function getLocalisationAdresse3(): ?string
    {
        return $this->localisationAdresse3;
    }

    public function setLocalisationAdresse3(?string $localisationAdresse3): self
    {
        $this->localisationAdresse3 = $localisationAdresse3;

        return $this;
    }

    public function getLocalisationCodePostal(): ?string
    {
        return $this->localisationCodePostal;
    }

    public function setLocalisationCodePostal(?string $localisationCodePostal): self
    {
        $this->localisationCodePostal = $localisationCodePostal;

        return $this;
    }

    public function getLocalisationVille(): ?string
    {
        return $this->localisationVille;
    }

    public function setLocalisationVille(?string $localisationVille): self
    {
        $this->localisationVille = $localisationVille;

        return $this;
    }

    public function getLocalisationLocalisationInsee(): ?string
    {
        return $this->localisationLocalisationInsee;
    }

    public function setLocalisationLocalisationInsee(?string $localisationLocalisationInsee): self
    {
        $this->localisationLocalisationInsee = $localisationLocalisationInsee;

        return $this;
    }

    public function getSasAdresse(): ?int
    {
        return $this->sasAdresse;
    }

    public function setSasAdresse(?int $sasAdresse): self
    {
        $this->sasAdresse = $sasAdresse;

        return $this;
    }

    public function getSasLogicielProvenance(): ?string
    {
        return $this->sasLogicielProvenance;
    }

    public function setSasLogicielProvenance(?string $sasLogicielProvenance): self
    {
        $this->sasLogicielProvenance = $sasLogicielProvenance;

        return $this;
    }

    public function getReferenceDossier(): ?string
    {
        return $this->referenceDossier;
    }

    public function setReferenceDossier(?string $referenceDossier): self
    {
        $this->referenceDossier = $referenceDossier;

        return $this;
    }

    public function getSasTypeDossier(): ?SISHDossierType
    {
        return $this->sasTypeDossier;
    }

    public function setSasTypeDossier(?SISHDossierType $sasTypeDossier): self
    {
        $this->sasTypeDossier = $sasTypeDossier;

        return $this;
    }

    public function getSasDateAffectation(): ?string
    {
        return $this->sasDateAffectation;
    }

    public function setSasDateAffectation(?string $sasDateAffectation): self
    {
        $this->sasDateAffectation = $sasDateAffectation;

        return $this;
    }

    public function getLocalisationEtage(): ?string
    {
        return $this->localisationEtage;
    }

    public function setLocalisationEtage(?string $localisationEtage): self
    {
        $this->localisationEtage = $localisationEtage;

        return $this;
    }

    public function getLocalisationEscalier(): ?string
    {
        return $this->localisationEscalier;
    }

    public function setLocalisationEscalier(?string $localisationEscalier): self
    {
        $this->localisationEscalier = $localisationEscalier;

        return $this;
    }

    public function getLocalisationNumPorte(): ?string
    {
        return $this->localisationNumPorte;
    }

    public function setLocalisationNumPorte(?string $localisationNumPorte): self
    {
        $this->localisationNumPorte = $localisationNumPorte;

        return $this;
    }

    public function getSitOccupantNbAdultes(): ?string
    {
        return $this->sitOccupantNbAdultes;
    }

    public function setSitOccupantNbAdultes(?string $sitOccupantNbAdultes): self
    {
        $this->sitOccupantNbAdultes = $sitOccupantNbAdultes;

        return $this;
    }

    public function getSitOccupantNbEnfantsM6(): ?string
    {
        return $this->sitOccupantNbEnfantsM6;
    }

    public function setSitOccupantNbEnfantsM6(?string $sitOccupantNbEnfantsM6): self
    {
        $this->sitOccupantNbEnfantsM6 = $sitOccupantNbEnfantsM6;

        return $this;
    }

    public function getSitOccupantNbEnfantsP6(): ?string
    {
        return $this->sitOccupantNbEnfantsP6;
    }

    public function setSitOccupantNbEnfantsP6(?string $sitOccupantNbEnfantsP6): self
    {
        $this->sitOccupantNbEnfantsP6 = $sitOccupantNbEnfantsP6;

        return $this;
    }

    public function getSitOccupantNbOccupants(): ?int
    {
        return $this->sitOccupantNbOccupants;
    }

    public function setSitOccupantNbOccupants(?int $sitOccupantNbOccupants): self
    {
        $this->sitOccupantNbOccupants = $sitOccupantNbOccupants;

        return $this;
    }

    public function getSitOccupantNumAllocataire(): ?string
    {
        return $this->sitOccupantNumAllocataire;
    }

    public function setSitOccupantNumAllocataire(?string $sitOccupantNumAllocataire): self
    {
        $this->sitOccupantNumAllocataire = $sitOccupantNumAllocataire;

        return $this;
    }

    public function getSitOccupantMontantAllocation(): ?float
    {
        return $this->sitOccupantMontantAllocation;
    }

    public function setSitOccupantMontantAlloc(?float $sitOccupantMontantAllocation): self
    {
        $this->sitOccupantMontantAllocation = $sitOccupantMontantAllocation;

        return $this;
    }

    public function getSitLogementBailEncours(): ?int
    {
        return $this->sitLogementBailEncours;
    }

    public function setSitLogementBailEncours(?int $sitLogementBailEncours): self
    {
        $this->sitLogementBailEncours = $sitLogementBailEncours;

        return $this;
    }

    public function getSitLogementBailDateEntree(): ?string
    {
        return $this->sitLogementBailDateEntree;
    }

    public function setSitLogementBailDateEntree(?string $sitLogementBailDateEntree): self
    {
        $this->sitLogementBailDateEntree = $sitLogementBailDateEntree;

        return $this;
    }

    public function getSitLogementPreavisDepart(): ?int
    {
        return $this->sitLogementPreavisDepart;
    }

    public function setSitLogementPreavisDepart(?int $sitLogementPreavisDepart): self
    {
        $this->sitLogementPreavisDepart = $sitLogementPreavisDepart;

        return $this;
    }

    public function getSitLogementRelogement(): ?int
    {
        return $this->sitLogementRelogement;
    }

    public function setSitLogementRelogement(?int $sitLogementRelogement): self
    {
        $this->sitLogementRelogement = $sitLogementRelogement;

        return $this;
    }

    public function getSitLogementSuperficie(): ?float
    {
        return $this->sitLogementSuperficie;
    }

    public function setSitLogementSuperficie(?float $sitLogementSuperficie): self
    {
        $this->sitLogementSuperficie = $sitLogementSuperficie;

        return $this;
    }

    public function getSitLogementMontantLoyer(): ?float
    {
        return $this->sitLogementMontantLoyer;
    }

    public function setSitLogementMontantLoyer(?float $sitLogementMontantLoyer): self
    {
        $this->sitLogementMontantLoyer = $sitLogementMontantLoyer;

        return $this;
    }

    public function getDeclarantNonOccupant(): ?int
    {
        return $this->declarantNonOccupant;
    }

    public function setDeclarantNonOccupant(?int $declarantNonOccupant): self
    {
        $this->declarantNonOccupant = $declarantNonOccupant;

        return $this;
    }

    public function getLogementNature(): ?string
    {
        return $this->logementNature;
    }

    public function setLogementNature(?string $logementNature): self
    {
        $this->logementNature = $logementNature;

        return $this;
    }

    public function getLogementType(): ?string
    {
        return $this->logementType;
    }

    public function setLogementType(?string $logementType): self
    {
        $this->logementType = $logementType;

        return $this;
    }

    public function getLogementSocial(): ?int
    {
        return $this->logementSocial;
    }

    public function setLogementSocial(?int $logementSocial): self
    {
        $this->logementSocial = $logementSocial;

        return $this;
    }

    public function getLogementAnneeConstruction(): ?string
    {
        return $this->logementAnneeConstruction;
    }

    public function setLogementAnneeConstruction(?string $logementAnneeConstruction): self
    {
        $this->logementAnneeConstruction = $logementAnneeConstruction;

        return $this;
    }

    public function getLogementTypeEnergie(): ?string
    {
        return $this->logementTypeEnergie;
    }

    public function setLogementTypeEnergie(?string $logementTypeEnergie): self
    {
        $this->logementTypeEnergie = $logementTypeEnergie;

        return $this;
    }

    public function getLogementCollectif(): ?int
    {
        return $this->logementCollectif;
    }

    public function setLogementCollectif(?int $logementCollectif): self
    {
        $this->logementCollectif = $logementCollectif;

        return $this;
    }

    public function getLogementAvant1949(): ?int
    {
        return $this->logementAvant1949;
    }

    public function setLogementAvant1949(?int $logementAvant1949): self
    {
        $this->logementAvant1949 = $logementAvant1949;

        return $this;
    }

    public function getLogementDiagST(): ?int
    {
        return $this->logementDiagST;
    }

    public function setLogementDiagST(?int $logementDiagST): self
    {
        $this->logementDiagST = $logementDiagST;

        return $this;
    }

    public function getLogementInvariant(): ?string
    {
        return $this->logementInvariant;
    }

    public function setLogementInvariant(?string $logementInvariant): self
    {
        $this->logementInvariant = $logementInvariant;

        return $this;
    }

    public function getLogementNbPieces(): ?int
    {
        return $this->logementNbPieces;
    }

    public function setLogementNbPieces(?int $logementNbPieces): self
    {
        $this->logementNbPieces = $logementNbPieces;

        return $this;
    }

    public function getLogementNbChambres(): ?int
    {
        return $this->logementNbChambres;
    }

    public function setLogementNbChambres(?int $logementNbChambres): self
    {
        $this->logementNbChambres = $logementNbChambres;

        return $this;
    }

    public function getLogementNbNiveaux(): ?int
    {
        return $this->logementNbNiveaux;
    }

    public function setLogementNbNiveaux(?int $logementNbNiveaux): self
    {
        $this->logementNbNiveaux = $logementNbNiveaux;

        return $this;
    }

    public function getProprietaireAverti(): ?int
    {
        return $this->proprietaireAverti;
    }

    public function setProprietaireAverti(?int $proprietaireAverti): self
    {
        $this->proprietaireAverti = $proprietaireAverti;

        return $this;
    }

    public function getProprietaireAvertiDate(): ?string
    {
        return $this->proprietaireAvertiDate;
    }

    public function setProprietaireAvertiDate(?string $proprietaireAvertiDate): self
    {
        $this->proprietaireAvertiDate = $proprietaireAvertiDate;

        return $this;
    }

    public function getProprietaireAvertiMoyen(): ?string
    {
        return $this->proprietaireAvertiMoyen;
    }

    public function setProprietaireAvertiMoyen(?string $proprietaireAvertiMoyen): self
    {
        $this->proprietaireAvertiMoyen = $proprietaireAvertiMoyen;

        return $this;
    }

    public function getSignalementScore(): ?float
    {
        return $this->signalementScore;
    }

    public function setSignalementScore(?float $signalementScore): self
    {
        $this->signalementScore = $signalementScore;

        return $this;
    }

    public function getSignalementOrigine(): ?string
    {
        return $this->signalementOrigine;
    }

    public function setSignalementOrigine(?string $signalementOrigine): self
    {
        $this->signalementOrigine = $signalementOrigine;

        return $this;
    }

    public function getSignalementNumero(): ?string
    {
        return $this->signalementNumero;
    }

    public function setSignalementNumero(?string $signalementNumero): self
    {
        $this->signalementNumero = $signalementNumero;

        return $this;
    }

    public function getSignalementCommentaire(): ?string
    {
        return $this->signalementCommentaire;
    }

    public function setSignalementCommentaire(?string $signalementCommentaire): self
    {
        $this->signalementCommentaire = $signalementCommentaire;

        return $this;
    }

    public function getSignalementDate(): ?string
    {
        return $this->signalementDate;
    }

    public function setSignalementDate(?string $signalementDate): self
    {
        $this->signalementDate = $signalementDate;

        return $this;
    }

    public function getSignalementDetails(): ?string
    {
        return $this->signalementDetails;
    }

    public function setSignalementDetails(?string $signalementDetails): self
    {
        $this->signalementDetails = $signalementDetails;

        return $this;
    }

    public function getSignalementProblemes(): ?string
    {
        return $this->signalementProblemes;
    }

    public function setSignalementProblemes(?string $signalementProblemes): self
    {
        $this->signalementProblemes = $signalementProblemes;

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

    public function getPiecesJointesDocuments(): array
    {
        return $this->piecesJointesDocuments;
    }

    public function setPiecesJointesDocuments(array $piecesJointesDocuments): self
    {
        $this->piecesJointesDocuments = $piecesJointesDocuments;

        return $this;
    }

    public function getSasDossierId(): ?int
    {
        return $this->sasDossierId;
    }

    public function setSasDossierId(?int $sasDossierId): self
    {
        $this->sasDossierId = $sasDossierId;

        return $this;
    }

    public function getPersonneType(): ?string
    {
        return $this->personneType;
    }

    public function setPersonneType(?string $personneType): self
    {
        $this->personneType = $personneType;

        return $this;
    }

    public function getPersonneNom(): ?string
    {
        return $this->personneNom;
    }

    public function setPersonneNom(?string $personneNom): self
    {
        $this->personneNom = $personneNom;

        return $this;
    }

    public function getPersonnePrenom(): ?string
    {
        return $this->personnePrenom;
    }

    public function setPersonnePrenom(?string $personnePrenom): self
    {
        $this->personnePrenom = $personnePrenom;

        return $this;
    }

    public function getPersonneTelephone(): ?string
    {
        return $this->personneTelephone;
    }

    public function setPersonneTelephone(?string $personneTelephone): self
    {
        $this->personneTelephone = $personneTelephone;

        return $this;
    }

    public function getPersonneEmail(): ?string
    {
        return $this->personneEmail;
    }

    public function setPersonneEmail(?string $personneEmail): self
    {
        $this->personneEmail = $personneEmail;

        return $this;
    }

    public function getPersonneLienOccupant(): ?string
    {
        return $this->personneLienOccupant;
    }

    public function setPersonneLienOccupant(?string $personneLienOccupant): self
    {
        $this->personneLienOccupant = $personneLienOccupant;

        return $this;
    }

    public function getPersonneStructure(): ?string
    {
        return $this->personneStructure;
    }

    public function setPersonneStructure(?string $personneStructure): self
    {
        $this->personneStructure = $personneStructure;

        return $this;
    }

    public function getPersonneAdresse(): ?string
    {
        return $this->personneAdresse;
    }

    public function setPersonneAdresse(?string $personneAdresse): self
    {
        $this->personneAdresse = $personneAdresse;

        return $this;
    }

    public function getPersonneRepresentant(): ?string
    {
        return $this->personneRepresentant;
    }

    public function setPersonneRepresentant(?string $personneRepresentant): self
    {
        $this->personneRepresentant = $personneRepresentant;

        return $this;
    }
}
