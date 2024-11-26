<?php

namespace App\Dto\Api\Response;

use App\Entity\Enum\DesordreCritereZone;
use App\Entity\Signalement;
use App\Service\Signalement\SignalementDesordresProcessor;
use OpenApi\Attributes as OA;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

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
    public GeolocalisationResponse $geoLocalisation;
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
    public ?string $codeInseeOccupant;
    public ?string $etageOccupant;
    public ?string $escalierOccupant;
    public ?string $numAppartOccupant;
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
    public ?float $loyer;
    public ?bool $bailEnCours;
    public ?bool $bailExistant;
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
        items: new OA\Items(ref: DesordreResponse::class)
    )]
    public array $desordres = [];
    public ?float $score;
    public ?float $scoreBatiment;
    public ?float $scoreLogement;
    // tags, qualifications, suivis, affectations, interventions, files
    public array $tags = [];
    public array $qualifications = [];
    #[OA\Property(
        type: 'array',
        items: new OA\Items(ref: SuiviResponse::class)
    )]
    public array $suivis = [];
    #[OA\Property(
        type: 'array',
        items: new OA\Items(ref: AffectationResponse::class)
    )]
    public array $affectations = [];
    #[OA\Property(
        type: 'array',
        items: new OA\Items(ref: InterventionResponse::class)
    )]
    public array $interventions = [];
    #[OA\Property(
        type: 'array',
        items: new OA\Items(ref: FileResponse::class)
    )]
    public array $files = [];
    // divers
    public ?string $territoire;
    public bool $signalementImporte;

    public function __construct(
        Signalement $signalement,
        SignalementDesordresProcessor $signalementDesordresProcessor,
        UrlGeneratorInterface $urlGenerator,
    ) {
        // references, dates et statut
        $this->uuid = $signalement->getUuid();
        $this->reference = $signalement->getReference();
        $this->dateCreation = $signalement->getCreatedAt()->format(\DATE_ATOM);
        $this->statut = $signalement->getStatut(); // envoyer un libellé ?
        $this->dateValidation = $signalement->getValidatedAt()?->format(\DATE_ATOM);
        $this->dateCloture = $signalement->getClosedAt()?->format(\DATE_ATOM);
        $this->motifCloture = $signalement->getMotifCloture()?->label();
        $this->motifRefus = $signalement->getMotifRefus()?->label();
        $this->abandonProcedureUsager = $signalement->getIsUsagerAbandonProcedure();
        // type declarant et details
        $this->typeDeclarant = $signalement->getProfileDeclarant()?->label();
        $this->precisionTypeSiBailleur = $signalement->getTypeProprio()?->label();
        $this->lienDeclarantOccupantSiTiers = $signalement->getLienDeclarantOccupant();
        $this->details = $signalement->getDetails(); // renomer ?
        // infos logement
        $this->natureLogement = $signalement->getNatureLogement();
        $this->precisionNatureLogement = $signalement->getTypeCompositionLogement()?->getTypeLogementNatureAutrePrecision();
        $this->logementSocial = $signalement->getIsLogementSocial();
        $this->superficie = $signalement->getSuperficie();
        $this->pieceUnique = $this->stringToBool($signalement->getTypeCompositionLogement()?->getCompositionLogementPieceUnique());
        $this->nbPieces = $signalement->getTypeCompositionLogement()?->getCompositionLogementNbPieces() ?? $signalement->getNbPiecesLogement();
        $this->anneeConstruction = $signalement->getInformationComplementaire()?->getInformationsComplementairesLogementAnneeConstruction() ?? $signalement->getAnneeConstruction();
        $this->constructionAvant1949 = $signalement->getIsConstructionAvant1949();
        $this->nbNiveaux = $signalement->getInformationComplementaire()?->getInformationsComplementairesLogementNombreEtages() ?? $signalement->getNbNiveauxLogement();
        $this->rezDeChaussee = $this->stringToBool($signalement->getTypeCompositionLogement()?->getTypeLogementRdc());
        $this->dernierEtage = $this->stringToBool($signalement->getTypeCompositionLogement()?->getTypeLogementDernierEtage());
        $this->sousSolSansFenetre = $this->stringToBool($signalement->getTypeCompositionLogement()?->getTypeLogementSousSolSansFenetre());
        $this->sousCombleSansFenetre = $this->stringToBool($signalement->getTypeCompositionLogement()?->getTypeLogementSousCombleSansFenetre());
        $this->pieceAVivreSuperieureA9m = $this->stringToBool($signalement->getTypeCompositionLogement()?->getTypeLogementCommoditesPieceAVivre9m());
        $this->cuisine = $this->stringToBool($signalement->getTypeCompositionLogement()?->getTypeLogementCommoditesCuisine());
        $this->cuisineCollective = $this->stringToBool($signalement->getTypeCompositionLogement()?->getTypeLogementCommoditesCuisineCollective());
        $this->salleDeBain = $this->stringToBool($signalement->getTypeCompositionLogement()?->getTypeLogementCommoditesSalleDeBain());
        $this->salleDeBainCollective = $this->stringToBool($signalement->getTypeCompositionLogement()?->getTypeLogementCommoditesSalleDeBainCollective());
        $this->wc = $this->stringToBool($signalement->getTypeCompositionLogement()?->getTypeLogementCommoditesWc());
        $this->wcDansCuisine = $this->stringToBool($signalement->getTypeCompositionLogement()?->getTypeLogementCommoditesWcCuisine());
        $this->wcCollectif = $this->stringToBool($signalement->getTypeCompositionLogement()?->getTypeLogementCommoditesWcCollective());
        $this->hauteurSuperieureA2metres = $this->stringToBool($signalement->getTypeCompositionLogement()?->getCompositionLogementHauteur());
        $this->dpeExistant = $this->stringToBool($signalement->getTypeCompositionLogement()?->getBailDpeDpe());
        $this->geoLocalisation = new GeolocalisationResponse($signalement->getGeoloc()['lat'] ?? null, $signalement->getGeoloc()['lng'] ?? null);
        // infos declarant
        $this->structureDeclarant = $signalement->getStructureDeclarant();
        $this->nomDeclarant = $signalement->getNomDeclarant();
        $this->prenomDeclarant = $signalement->getPrenomDeclarant();
        $this->telephoneDeclarant = $signalement->getTelDeclarantDecoded();
        $this->telephoneSecondaireDeclarant = $signalement->getTelDeclarantSecondaireDecoded();
        $this->mailDeclarant = $signalement->getMailDeclarant();
        $this->estTravailleurSocialPourOccupant = $this->stringToBool($signalement->getSituationFoyer()?->getTravailleurSocialAccompagnementDeclarant());
        // infos occupants
        $this->civiliteOccupant = $signalement->getCiviliteOccupant();
        $this->nomOccupant = $signalement->getNomOccupant();
        $this->prenomOccupant = $signalement->getPrenomOccupant();
        $this->telephoneOccupant = $signalement->getTelOccupantDecoded();
        $this->telephoneSecondaireOccupant = $signalement->getTelOccupantBisDecoded();
        $this->mailOccupant = $signalement->getMailOccupant();
        $this->adresseOccupant = $signalement->getAdresseOccupant();
        $this->codePostalOccupant = $signalement->getCpOccupant();
        $this->villeOccupant = $signalement->getVilleOccupant();
        $this->codeInseeOccupant = $signalement->getInseeOccupant();
        $this->etageOccupant = $signalement->getEtageOccupant();
        $this->escalierOccupant = $signalement->getEscalierOccupant();
        $this->numAppartOccupant = $signalement->getNumAppartOccupant();
        $this->adresseAutreOccupant = $signalement->getAdresseAutreOccupant();
        $this->dateNaissanceOccupant = $signalement->getDateNaissanceOccupant()?->format('Y-m-d') ?? $signalement->getInformationComplementaire()?->getInformationsComplementairesSituationOccupantsDateNaissance();
        $this->dateEntreeLogement = $signalement->getDateEntree()?->format('Y-m-d');
        $this->nbOccupantsLogement = $signalement->getNbOccupantsLogement();
        $this->enfantsDansLogement = $this->stringToBool($signalement->getTypeCompositionLogement()?->getCompositionLogementEnfants());
        $this->assuranceContactee = $this->stringToBool($signalement->getInformationProcedure()?->getInfoProcedureAssuranceContactee());
        $this->reponseAssurance = $signalement->getInformationProcedure()?->getInfoProcedureReponseAssurance();
        $this->souhaiteQuitterLogement = $this->stringToBool($signalement->getSituationFoyer()?->getTravailleurSocialQuitteLogement());
        $this->souhaiteQuitterLogementApresTravaux = $this->stringToBool($signalement->getInformationProcedure()?->getInfoProcedureDepartApresTravaux());
        $this->suiviParTravailleurSocial = $this->stringToBool($signalement->getSituationFoyer()?->getTravailleurSocialAccompagnement());
        $this->revenuFiscalOccupant = $signalement->getInformationComplementaire()?->getInformationsComplementairesSituationOccupantsRevenuFiscal();
        // infos proprietaire
        $this->nomProprietaire = $signalement->getNomProprio();
        $this->prenomProprietaire = $signalement->getPrenomProprio();
        $this->adresseProprietaire = $signalement->getAdresseProprio();
        $this->codePostalProprietaire = $signalement->getCodePostalProprio();
        $this->villeProprietaire = $signalement->getVilleProprio();
        $this->telephoneProprietaire = $signalement->getTelProprioDecoded();
        $this->telephoneSecondaireProprietaire = $signalement->getTelProprioSecondaireDecoded();
        $this->mailProprietaire = $signalement->getMailProprio();
        $this->proprietaireDateNaissance = $signalement->getInformationComplementaire()?->getInformationsComplementairesSituationBailleurDateNaissance();
        $this->proprietaireRevenuFiscal = $signalement->getInformationComplementaire()?->getInformationsComplementairesSituationBailleurRevenuFiscal() ?: null;
        $this->proprietaireBeneficiaireRsa = $this->stringToBool($signalement->getInformationComplementaire()?->getInformationsComplementairesSituationBailleurBeneficiaireRsa());
        $this->proprietaireBeneficiaireFsl = $this->stringToBool($signalement->getInformationComplementaire()?->getInformationsComplementairesSituationBailleurBeneficiaireFsl());
        // infos location
        $this->proprietaireAverti = $signalement->getIsProprioAverti();
        $this->loyer = $signalement->getLoyer();
        $this->bailEnCours = $signalement->getIsBailEnCours();
        $this->bailExistant = $this->stringToBool($signalement->getTypeCompositionLogement()?->getBailDpeBail());
        $this->etatDesLieuxExistant = $this->stringToBool($signalement->getTypeCompositionLogement()?->getBailDpeEtatDesLieux());
        $this->preavisDepartTransmis = $signalement->getisPreavisDepart();
        $this->demandeRelogementEffectuee = $signalement->getIsRelogement();
        $this->loyersPayes = $this->stringToBool($signalement->getInformationComplementaire()?->getinformationsComplementairesSituationOccupantsLoyersPayes());
        $this->dateEffetBail = $signalement->getInformationComplementaire()?->getInformationsComplementairesSituationBailleurDateEffetBail() ?? $this->dateEntreeLogement;
        // infos allocataire
        $this->allocataire = in_array($signalement->getIsAllocataire(), [null, '']) ? null : (bool) $signalement->getIsAllocataire(); // valeurs possibles : null, '', 0, 1, 'CAF', 'MSA'
        $this->typeAllocataire = in_array($signalement->getIsAllocataire(), ['MSA', 'CAF']) ? $signalement->getIsAllocataire() : null;
        $this->numAllocataire = $signalement->getNumAllocataire();
        $this->montantAllocation = $signalement->getSituationFoyer()?->getLogementSocialMontantAllocation() ?? $signalement->getMontantAllocation();
        $this->beneficiaireRSA = $this->stringToBool($signalement->getInformationComplementaire()?->getInformationsComplementairesSituationOccupantsBeneficiaireRsa()) ?? $signalement->getIsRsa();
        $this->beneficiaireFSL = $this->stringToBool($signalement->getInformationComplementaire()?->getInformationsComplementairesSituationOccupantsBeneficiaireFsl()) ?? $signalement->getIsFondSolidariteLogement();
        // désordres
        $desordresInfos = $signalementDesordresProcessor->process($signalement);
        if (!$signalement->getCreatedFrom()) {
            foreach ($desordresInfos['criticitesArranged'] as $label => $data) {
                $this->desordres[] = new DesordreResponse($label, $data);
            }
        } else {
            foreach (DesordreCritereZone::getLabelList() as $zone => $unused) {
                if (isset($desordresInfos['criticitesArranged'][$zone])) {
                    foreach ($desordresInfos['criticitesArranged'][$zone] as $label => $data) {
                        $this->desordres[] = new DesordreResponse($label, $data, $zone);
                    }
                }
            }
        }
        $this->score = $signalement->getScore();
        $this->scoreBatiment = $signalement->getScoreBatiment();
        $this->scoreLogement = $signalement->getScoreLogement();
        // tags, qualifications, suivis, affectations, visites, files
        foreach ($signalement->getTags() as $tag) {
            $this->tags[] = $tag->getLabel();
        }
        foreach ($signalement->getSignalementQualifications() as $qualification) {
            if (!$qualification->isPostVisite()) {
                $this->qualifications[] = $qualification->getStatus()->label();
            }
        }
        foreach ($signalement->getSuivis() as $suivi) {
            $this->suivis[] = new SuiviResponse($suivi);
        }
        foreach ($signalement->getAffectations() as $affectation) {
            $this->affectations[] = new AffectationResponse($affectation);
        }
        foreach ($signalement->getInterventions() as $visite) {
            $this->interventions[] = new InterventionResponse($visite);
        }
        foreach ($signalement->getFiles() as $file) {
            $this->files[] = new FileResponse($file, $urlGenerator);
        }
        // divers
        $this->territoire = $signalement->getTerritory()?->getName(); // retirer car ce sera toujours le même pour un utilisateur ?
        $this->signalementImporte = $signalement->getIsImported();
    }

    private function stringToBool(?string $value): ?bool
    {
        if (in_array($value, ['oui', 'piece_unique'])) {
            return true;
        }
        if (in_array($value, ['non', 'plusieurs_pieces'])) {
            return false;
        }

        return null;
    }
}
