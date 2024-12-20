<?php

namespace App\Dto\Api\Response;

use App\Dto\Api\Model\Desordre;
use App\Dto\Api\Model\File;
use App\Dto\Api\Model\Geolocalisation;
use App\Dto\Api\Model\Intervention;
use App\Dto\Api\Model\Suivi;
use App\Entity\Enum\DebutDesordres;
use OpenApi\Attributes as OA;

class SignalementResponse
{
    // references, dates et statut
    public string $uuid;
    public string $reference;
    public string $dateCreation;
    public int $statut;
    public ?string $dateValidation;
    public ?string $dateCloture;
    public ?string $motifCloture;
    public ?string $motifRefus;
    public ?bool $abandonProcedureUsager;
    // type declarant et details
    public ?string $typeDeclarant;
    public ?string $precisionTypeSiBailleur;
    public ?string $lienDeclarantOccupantSiTiers;
    public ?string $details;
    // infos logement
    public ?string $natureLogement;
    public ?string $precisionNatureLogement;
    public ?bool $logementSocial;
    public ?float $superficie;
    public ?bool $pieceUnique;
    public ?string $nbPieces;
    public ?string $anneeConstruction;
    public ?bool $constructionAvant1949;
    public ?string $nbNiveaux;
    public ?bool $rezDeChaussee;
    public ?bool $dernierEtage;
    public ?bool $sousSolSansFenetre;
    public ?bool $sousCombleSansFenetre;
    public ?bool $pieceAVivreSuperieureA9m;
    public ?bool $cuisine;
    public ?bool $cuisineCollective;
    public ?bool $salleDeBain;
    public ?bool $salleDeBainCollective;
    public ?bool $wc;
    public ?bool $wcDansCuisine;
    public ?bool $wcCollectif;
    public ?bool $hauteurSuperieureA2metres;
    public ?bool $dpeExistant;
    public ?string $dpeClasseEnergetique;
    public Geolocalisation $geoLocalisation;
    // infos declarant
    public ?string $structureDeclarant;
    public ?string $nomDeclarant;
    public ?string $prenomDeclarant;
    public ?string $telephoneDeclarant;
    public ?string $telephoneSecondaireDeclarant;
    public ?string $mailDeclarant;
    public ?bool $estTravailleurSocialPourOccupant;
    // infos occupant
    public ?string $civiliteOccupant;
    public ?string $nomOccupant;
    public ?string $prenomOccupant;
    public ?string $telephoneOccupant;
    public ?string $telephoneSecondaireOccupant;
    public ?string $mailOccupant;
    public ?string $adresseOccupant;
    public ?string $codePostalOccupant;
    public ?string $villeOccupant;
    public ?string $etageOccupant;
    public ?string $escalierOccupant;
    public ?string $numAppartOccupant;
    public ?string $codeInseeOccupant;
    public ?string $cleBanAdresseOccupant;
    public ?string $adresseAutreOccupant;
    public ?string $dateNaissanceOccupant;
    public ?string $dateEntreeLogement;
    public ?int $nbOccupantsLogement;
    public ?bool $enfantsDansLogement;
    public ?bool $assuranceContactee;
    public ?string $reponseAssurance;
    public ?bool $souhaiteQuitterLogement;
    public ?bool $souhaiteQuitterLogementApresTravaux;
    public ?bool $suiviParTravailleurSocial;
    public ?string $revenuFiscalOccupant;
    // infos proprietaire
    public ?string $typeProprietaire;
    public ?string $nomProprietaire;
    public ?string $prenomProprietaire;
    public ?string $adresseProprietaire;
    public ?string $codePostalProprietaire;
    public ?string $villeProprietaire;
    public ?string $telephoneProprietaire;
    public ?string $telephoneSecondaireProprietaire;
    public ?string $mailProprietaire;
    public ?string $proprietaireRevenuFiscal;
    public ?bool $proprietaireBeneficiaireRsa;
    public ?bool $proprietaireBeneficiaireFsl;
    public ?string $proprietaireDateNaissance;
    // infos location
    public ?bool $proprietaireAverti;
    public ?string $moyenInformationProprietaire;
    public ?string $dateInformationProprietaire;
    public ?string $reponseProprietaire;
    public ?string $numeroReclamationProprietaire;
    public ?float $loyer;
    public ?bool $bailEnCours;
    public ?bool $bailExistant;
    public ?string $invariantFiscal;
    public ?bool $etatDesLieuxExistant;
    public ?bool $preavisDepartTransmis;
    public ?bool $demandeRelogementEffectuee;
    public ?bool $loyersPayes;
    public ?string $dateEffetBail;
    // infos allocataire
    public ?bool $allocataire;
    public ?string $typeAllocataire;
    public ?string $numAllocataire;
    public ?string $montantAllocation;
    public ?bool $beneficiaireRSA;
    public ?bool $beneficiaireFSL;
    // désordres
    #[OA\Property(
        type: 'array',
        items: new OA\Items(ref: Desordre::class)
    )]
    public array $desordres = [];
    public ?float $score;
    public ?float $scoreBatiment;
    public ?float $scoreLogement;
    public ?DebutDesordres $debutDesordres = null;
    public ?bool $desordresConstates = null;
    // tags, qualifications, suivis, affectations, interventions, files
    public array $tags = [];
    public array $qualifications = [];
    #[OA\Property(
        type: 'array',
        items: new OA\Items(ref: Suivi::class)
    )]
    public array $suivis = [];
    #[OA\Property(
        type: 'array',
        items: new OA\Items(ref: Intervention::class)
    )]
    public array $interventions = [];
    #[OA\Property(
        type: 'array',
        items: new OA\Items(ref: File::class)
    )]
    public array $files = [];
    // divers
    public ?string $territoireNom;
    public ?string $territoireCode;
    public bool $signalementImporte;
}
