<?php

namespace App\Factory\Api;

use App\Dto\Api\Model\Desordre;
use App\Dto\Api\Model\Geolocalisation;
use App\Dto\Api\Model\Intervention;
use App\Dto\Api\Model\Suivi;
use App\Dto\Api\Response\SignalementResponse;
use App\Entity\Enum\DesordreCritereZone;
use App\Entity\Signalement;
use App\Service\Signalement\SignalementDesordresProcessor;

class SignalementResponseFactory
{
    public function __construct(
        private readonly SignalementDesordresProcessor $signalementDesordresProcessor,
        private readonly FileFactory $fileFactory,
    ) {
    }

    public function createFromSignalement(Signalement $signalement): SignalementResponse
    {
        $signalementResponse = new SignalementResponse();
        // references, dates et statut
        $signalementResponse->uuid = $signalement->getUuid();
        $signalementResponse->reference = $signalement->getReference();
        $signalementResponse->dateCreation = $signalement->getCreatedAt()->format(\DATE_ATOM);
        $signalementResponse->statut = $signalement->getStatut(); // envoyer un libellé ?
        $signalementResponse->dateValidation = $signalement->getValidatedAt()?->format(\DATE_ATOM);
        $signalementResponse->dateCloture = $signalement->getClosedAt()?->format(\DATE_ATOM);
        $signalementResponse->motifCloture = $signalement->getMotifCloture()?->value;
        $signalementResponse->motifRefus = $signalement->getMotifRefus()?->value;
        $signalementResponse->abandonProcedureUsager = $signalement->getIsUsagerAbandonProcedure();
        // type declarant et details
        $signalementResponse->typeDeclarant = $signalement->getProfileDeclarant()?->value;
        $signalementResponse->precisionTypeSiBailleur = $signalement->getTypeProprio()?->value;
        $signalementResponse->lienDeclarantOccupantSiTiers = $signalement->getLienDeclarantOccupant();
        $signalementResponse->details = $signalement->getDetails(); // renomer ?
        // infos logement
        $signalementResponse->natureLogement = $signalement->getNatureLogement();
        $signalementResponse->precisionNatureLogement = $signalement->getTypeCompositionLogement()?->getTypeLogementNatureAutrePrecision();
        $signalementResponse->logementSocial = $signalement->getIsLogementSocial();
        $signalementResponse->superficie = $signalement->getSuperficie();
        $signalementResponse->pieceUnique = $this->stringToBool($signalement->getTypeCompositionLogement()?->getCompositionLogementPieceUnique());
        $signalementResponse->nbPieces = $signalement->getTypeCompositionLogement()?->getCompositionLogementNbPieces() ?? $signalement->getNbPiecesLogement();
        $signalementResponse->anneeConstruction = $signalement->getInformationComplementaire()?->getInformationsComplementairesLogementAnneeConstruction() ?? $signalement->getAnneeConstruction();
        $signalementResponse->constructionAvant1949 = $signalement->getIsConstructionAvant1949();
        $signalementResponse->nbNiveaux = $signalement->getInformationComplementaire()?->getInformationsComplementairesLogementNombreEtages() ?? $signalement->getNbNiveauxLogement();
        $signalementResponse->rezDeChaussee = $this->stringToBool($signalement->getTypeCompositionLogement()?->getTypeLogementRdc());
        $signalementResponse->dernierEtage = $this->stringToBool($signalement->getTypeCompositionLogement()?->getTypeLogementDernierEtage());
        $signalementResponse->sousSolSansFenetre = $this->stringToBool($signalement->getTypeCompositionLogement()?->getTypeLogementSousSolSansFenetre());
        $signalementResponse->sousCombleSansFenetre = $this->stringToBool($signalement->getTypeCompositionLogement()?->getTypeLogementSousCombleSansFenetre());
        $signalementResponse->pieceAVivreSuperieureA9m = $this->stringToBool($signalement->getTypeCompositionLogement()?->getTypeLogementCommoditesPieceAVivre9m());
        $signalementResponse->cuisine = $this->stringToBool($signalement->getTypeCompositionLogement()?->getTypeLogementCommoditesCuisine());
        $signalementResponse->cuisineCollective = $this->stringToBool($signalement->getTypeCompositionLogement()?->getTypeLogementCommoditesCuisineCollective());
        $signalementResponse->salleDeBain = $this->stringToBool($signalement->getTypeCompositionLogement()?->getTypeLogementCommoditesSalleDeBain());
        $signalementResponse->salleDeBainCollective = $this->stringToBool($signalement->getTypeCompositionLogement()?->getTypeLogementCommoditesSalleDeBainCollective());
        $signalementResponse->wc = $this->stringToBool($signalement->getTypeCompositionLogement()?->getTypeLogementCommoditesWc());
        $signalementResponse->wcDansCuisine = $this->stringToBool($signalement->getTypeCompositionLogement()?->getTypeLogementCommoditesWcCuisine());
        $signalementResponse->wcCollectif = $this->stringToBool($signalement->getTypeCompositionLogement()?->getTypeLogementCommoditesWcCollective());
        $signalementResponse->hauteurSuperieureA2metres = $this->stringToBool($signalement->getTypeCompositionLogement()?->getCompositionLogementHauteur());
        $signalementResponse->dpeExistant = $this->stringToBool($signalement->getTypeCompositionLogement()?->getBailDpeDpe());
        $signalementResponse->dpeClasseEnergetique = $signalement->getTypeCompositionLogement()?->getBailDpeClasseEnergetique();
        $signalementResponse->geoLocalisation = new Geolocalisation($signalement->getGeoloc()['lat'] ?? null, $signalement->getGeoloc()['lng'] ?? null);
        // infos declarant
        $signalementResponse->structureDeclarant = $signalement->getStructureDeclarant();
        $signalementResponse->nomDeclarant = $signalement->getNomDeclarant();
        $signalementResponse->prenomDeclarant = $signalement->getPrenomDeclarant();
        $signalementResponse->telephoneDeclarant = $signalement->getTelDeclarantDecoded();
        $signalementResponse->telephoneSecondaireDeclarant = $signalement->getTelDeclarantSecondaireDecoded();
        $signalementResponse->mailDeclarant = $signalement->getMailDeclarant();
        $signalementResponse->estTravailleurSocialPourOccupant = $this->stringToBool($signalement->getSituationFoyer()?->getTravailleurSocialAccompagnementDeclarant());
        // infos occupants
        $signalementResponse->civiliteOccupant = $signalement->getCiviliteOccupant();
        $signalementResponse->nomOccupant = $signalement->getNomOccupant();
        $signalementResponse->prenomOccupant = $signalement->getPrenomOccupant();
        $signalementResponse->telephoneOccupant = $signalement->getTelOccupantDecoded();
        $signalementResponse->telephoneSecondaireOccupant = $signalement->getTelOccupantBisDecoded();
        $signalementResponse->mailOccupant = $signalement->getMailOccupant();
        $signalementResponse->adresseOccupant = $signalement->getAdresseOccupant();
        $signalementResponse->codePostalOccupant = $signalement->getCpOccupant();
        $signalementResponse->villeOccupant = $signalement->getVilleOccupant();
        $signalementResponse->etageOccupant = $signalement->getEtageOccupant();
        $signalementResponse->escalierOccupant = $signalement->getEscalierOccupant();
        $signalementResponse->numAppartOccupant = $signalement->getNumAppartOccupant();
        $signalementResponse->adresseAutreOccupant = $signalement->getAdresseAutreOccupant();
        $signalementResponse->codeInseeOccupant = $signalement->getInseeOccupant();
        $signalementResponse->cleBanAdresseOccupant = $signalement->getBanIdOccupant();
        $signalementResponse->dateNaissanceOccupant = $signalement->getDateNaissanceOccupant()?->format('Y-m-d') ?? $signalement->getInformationComplementaire()?->getInformationsComplementairesSituationOccupantsDateNaissance();
        $signalementResponse->dateEntreeLogement = $signalement->getDateEntree()?->format('Y-m-d');
        $signalementResponse->nbOccupantsLogement = $signalement->getNbOccupantsLogement();
        $signalementResponse->enfantsDansLogement = $this->stringToBool($signalement->getTypeCompositionLogement()?->getCompositionLogementEnfants());
        $signalementResponse->assuranceContactee = $this->stringToBool($signalement->getInformationProcedure()?->getInfoProcedureAssuranceContactee());
        $signalementResponse->reponseAssurance = $signalement->getInformationProcedure()?->getInfoProcedureReponseAssurance();
        $signalementResponse->souhaiteQuitterLogement = $this->stringToBool($signalement->getSituationFoyer()?->getTravailleurSocialQuitteLogement());
        $signalementResponse->souhaiteQuitterLogementApresTravaux = $this->stringToBool($signalement->getInformationProcedure()?->getInfoProcedureDepartApresTravaux());
        $signalementResponse->suiviParTravailleurSocial = $this->stringToBool($signalement->getSituationFoyer()?->getTravailleurSocialAccompagnement());
        $signalementResponse->revenuFiscalOccupant = $signalement->getInformationComplementaire()?->getInformationsComplementairesSituationOccupantsRevenuFiscal();
        // infos proprietaire
        $signalementResponse->nomProprietaire = $signalement->getNomProprio();
        $signalementResponse->prenomProprietaire = $signalement->getPrenomProprio();
        $signalementResponse->adresseProprietaire = $signalement->getAdresseProprio();
        $signalementResponse->codePostalProprietaire = $signalement->getCodePostalProprio();
        $signalementResponse->villeProprietaire = $signalement->getVilleProprio();
        $signalementResponse->telephoneProprietaire = $signalement->getTelProprioDecoded();
        $signalementResponse->telephoneSecondaireProprietaire = $signalement->getTelProprioSecondaireDecoded();
        $signalementResponse->mailProprietaire = $signalement->getMailProprio();
        $signalementResponse->proprietaireDateNaissance = $signalement->getInformationComplementaire()?->getInformationsComplementairesSituationBailleurDateNaissance();
        $signalementResponse->proprietaireRevenuFiscal = $signalement->getInformationComplementaire()?->getInformationsComplementairesSituationBailleurRevenuFiscal() ?: null;
        $signalementResponse->proprietaireBeneficiaireRsa = $this->stringToBool($signalement->getInformationComplementaire()?->getInformationsComplementairesSituationBailleurBeneficiaireRsa());
        $signalementResponse->proprietaireBeneficiaireFsl = $this->stringToBool($signalement->getInformationComplementaire()?->getInformationsComplementairesSituationBailleurBeneficiaireFsl());
        // infos location
        $signalementResponse->proprietaireAverti = $signalement->getIsProprioAverti();
        $signalementResponse->loyer = $signalement->getLoyer();
        $signalementResponse->bailEnCours = $signalement->getIsBailEnCours();
        $signalementResponse->bailExistant = $this->stringToBool($signalement->getTypeCompositionLogement()?->getBailDpeBail());
        $signalementResponse->invariantFiscal = $signalement->getTypeCompositionLogement()?->getBailDpeInvariant();
        $signalementResponse->etatDesLieuxExistant = $this->stringToBool($signalement->getTypeCompositionLogement()?->getBailDpeEtatDesLieux());
        $signalementResponse->preavisDepartTransmis = $signalement->getisPreavisDepart();
        $signalementResponse->demandeRelogementEffectuee = $signalement->getIsRelogement();
        $signalementResponse->loyersPayes = $this->stringToBool($signalement->getInformationComplementaire()?->getinformationsComplementairesSituationOccupantsLoyersPayes());
        $signalementResponse->dateEffetBail = $signalement->getInformationComplementaire()?->getInformationsComplementairesSituationBailleurDateEffetBail() ?? $signalementResponse->dateEntreeLogement;
        // infos allocataire
        $signalementResponse->allocataire = in_array($signalement->getIsAllocataire(), [null, '']) ? null : (bool) $signalement->getIsAllocataire(); // valeurs possibles : null, '', 0, 1, 'CAF', 'MSA'
        $signalementResponse->typeAllocataire = in_array($signalement->getIsAllocataire(), ['MSA', 'CAF']) ? $signalement->getIsAllocataire() : null;
        $signalementResponse->numAllocataire = $signalement->getNumAllocataire();
        $signalementResponse->montantAllocation = $signalement->getSituationFoyer()?->getLogementSocialMontantAllocation() ?? $signalement->getMontantAllocation();
        $signalementResponse->beneficiaireRSA = $this->stringToBool($signalement->getInformationComplementaire()?->getInformationsComplementairesSituationOccupantsBeneficiaireRsa()) ?? $signalement->getIsRsa();
        $signalementResponse->beneficiaireFSL = $this->stringToBool($signalement->getInformationComplementaire()?->getInformationsComplementairesSituationOccupantsBeneficiaireFsl()) ?? $signalement->getIsFondSolidariteLogement();
        // désordres
        $desordresInfos = $this->signalementDesordresProcessor->process($signalement);
        if (!$signalement->getCreatedFrom()) {
            foreach ($desordresInfos['criticitesArranged'] as $label => $data) {
                $signalementResponse->desordres[] = new Desordre($label, $data);
            }
        } else {
            foreach (DesordreCritereZone::getLabelList() as $zone => $unused) {
                if (isset($desordresInfos['criticitesArranged'][$zone])) {
                    foreach ($desordresInfos['criticitesArranged'][$zone] as $label => $data) {
                        $signalementResponse->desordres[] = new Desordre($label, $data, $zone);
                    }
                }
            }
        }
        $signalementResponse->score = $signalement->getScore();
        $signalementResponse->scoreBatiment = $signalement->getScoreBatiment();
        $signalementResponse->scoreLogement = $signalement->getScoreLogement();
        $signalementResponse->debutDesordres = $signalement->getDebutDesordres();
        $signalementResponse->desordresConstates = $signalement->getHasSeenDesordres();
        // tags, qualifications, suivis, affectations, visites, files
        foreach ($signalement->getTags() as $tag) {
            $signalementResponse->tags[] = $tag->getLabel();
        }
        foreach ($signalement->getSignalementQualifications() as $qualification) {
            if (!$qualification->isPostVisite()) {
                $signalementResponse->qualifications[] = $qualification->getStatus()?->value;
            }
        }
        foreach ($signalement->getSuivis() as $suivi) {
            $signalementResponse->suivis[] = new Suivi($suivi);
        }
        foreach ($signalement->getInterventions() as $intervention) {
            $signalementResponse->interventions[] = new Intervention($intervention);
        }
        foreach ($signalement->getFiles() as $file) {
            $signalementResponse->files[] = $this->fileFactory->createFromSignalement($file);
        }
        // divers
        $signalementResponse->territoireNom = $signalement->getTerritory()?->getName();
        $signalementResponse->territoireCode = $signalement->getTerritory()?->getZip();
        $signalementResponse->signalementImporte = $signalement->getIsImported();

        return $signalementResponse;
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
