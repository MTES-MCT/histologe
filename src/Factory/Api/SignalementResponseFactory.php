<?php

namespace App\Factory\Api;

use App\Dto\Api\Model\Adresse;
use App\Dto\Api\Model\Affectation as AffectationModel;
use App\Dto\Api\Model\Desordre;
use App\Dto\Api\Model\Personne;
use App\Dto\Api\Model\Suivi;
use App\Dto\Api\Response\SignalementResponse;
use App\Entity\Affectation;
use App\Entity\Enum\AffectationStatus;
use App\Entity\Enum\Api\PersonneType;
use App\Entity\Enum\DesordreCritereZone;
use App\Entity\Enum\EtageType;
use App\Entity\Enum\InterventionType;
use App\Entity\Enum\ProprioType;
use App\Entity\Signalement;
use App\Entity\User;
use App\Service\Signalement\SignalementDesordresProcessor;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

readonly class SignalementResponseFactory
{
    public const array PERSONNE_TYPES = [
        PersonneType::OCCUPANT,
        PersonneType::DECLARANT,
        PersonneType::PROPRIETAIRE,
        PersonneType::AGENCE,
    ];

    public function __construct(
        private SignalementDesordresProcessor $signalementDesordresProcessor,
        private FileFactory $fileFactory,
        private VisiteFactory $visiteFactory,
        private Security $security,
        private ParameterBagInterface $parameterBag,
    ) {
    }

    public function createFromSignalement(Signalement $signalement): SignalementResponse
    {
        /** @var User $user */
        $user = $this->security->getUser();
        $partner = $user->getPartners()->first();

        /** @var Affectation $affectation */
        $affectation = $signalement->getAffectations()
            ->filter(
                function (Affectation $affectation) use ($partner) {
                    return $affectation->getPartner() === $partner;
                })
            ->current();

        $signalementResponse = new SignalementResponse();
        // references, dates et statut
        $signalementResponse->uuid = $signalement->getUuid();
        $signalementResponse->reference = $signalement->getReference();
        $signalementResponse->dateCreation = $signalement->getCreatedAt()->format(\DATE_ATOM);
        $signalementResponse->statut = $signalement->getStatut();
        $signalementResponse->dateValidation = $signalement->getValidatedAt()?->format(\DATE_ATOM);
        $signalementResponse->dateCloture = $signalement->getClosedAt()?->format(\DATE_ATOM);
        $signalementResponse->motifCloture = $signalement->getMotifCloture();
        $signalementResponse->motifRefus = $signalement->getMotifRefus();
        $signalementResponse->abandonProcedureUsager = $signalement->getIsUsagerAbandonProcedure();
        $signalementResponse->typeDeclarant = $signalement->getProfileDeclarant();
        $signalementResponse->description = $signalement->getDetails();

        $signalementResponse->affectation = new AffectationModel();
        $signalementResponse->affectation->uuid = $affectation->getUuid();
        $signalementResponse->affectation->statut = AffectationStatus::mapNewStatus($affectation->getStatut());
        $signalementResponse->affectation->dateAffectation = $affectation->getCreatedAt()->format(\DATE_ATOM);
        $signalementResponse->affectation->dateAcceptation = $affectation->getAnsweredAt()?->format(\DATE_ATOM);
        $signalementResponse->affectation->motifCloture = $affectation->getMotifCloture();
        $signalementResponse->affectation->motifRefus = $affectation->getMotifRefus();

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
        if (!empty($signalement->getTypeCompositionLogement()?->getTypeLogementAppartementEtage())) {
            $signalementResponse->etage = EtageType::tryFrom($signalement->getTypeCompositionLogement()?->getTypeLogementAppartementEtage());
        }
        $signalementResponse->avecFenetres = $signalement->getTypeCompositionLogement()?->getTypeLogementAppartementAvecFenetres();
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
        $signalementResponse->dateEntreeLogement = $signalement->getDateEntree()?->format('Y-m-d');
        $signalementResponse->nbOccupantsLogement = $signalement->getNbOccupantsLogement();
        $signalementResponse->nbEnfantsDansLogement = $this->stringToInt($signalement->getTypeCompositionLogement()?->getCompositionLogementNombreEnfants());
        $signalementResponse->enfantsDansLogementMoinsSixAns = $this->stringToBool($signalement->getTypeCompositionLogement()?->getCompositionLogementEnfants());
        $signalementResponse->assuranceContactee = $this->stringToBool($signalement->getInformationProcedure()?->getInfoProcedureAssuranceContactee());
        $signalementResponse->reponseAssurance = $signalement->getInformationProcedure()?->getInfoProcedureReponseAssurance();
        $signalementResponse->souhaiteQuitterLogement = $this->stringToBool($signalement->getSituationFoyer()?->getTravailleurSocialQuitteLogement());
        $signalementResponse->souhaiteQuitterLogementApresTravaux = $this->stringToBool($signalement->getInformationProcedure()?->getInfoProcedureDepartApresTravaux());
        $signalementResponse->suiviParTravailleurSocial = $this->stringToBool($signalement->getSituationFoyer()?->getTravailleurSocialAccompagnement());
        $signalementResponse->proprietaireAverti = $signalement->getIsProprioAverti();
        $signalementResponse->moyenInformationProprietaire = $signalement->getInformationProcedure()?->getInfoProcedureBailMoyen();
        $signalementResponse->dateInformationProprietaire = $signalement->getInformationProcedure()?->getInfoProcedureBailDate();
        $signalementResponse->reponseProprietaire = $signalement->getInformationProcedure()?->getInfoProcedureBailReponse();
        $signalementResponse->numeroReclamationProprietaire = $signalement->getInformationProcedure()?->getInfoProcedureBailNumero();
        $signalementResponse->loyer = $signalement->getLoyer();
        $signalementResponse->logementVacant = $signalement->getIsLogementVacant();
        $signalementResponse->bailEnCours = $signalement->getIsBailEnCours();
        $signalementResponse->bailExistant = $this->stringToBool($signalement->getTypeCompositionLogement()?->getBailDpeBail());
        $signalementResponse->invariantFiscal = $signalement->getTypeCompositionLogement()?->getBailDpeInvariant();
        $signalementResponse->etatDesLieuxExistant = $this->stringToBool($signalement->getTypeCompositionLogement()?->getBailDpeEtatDesLieux());
        $signalementResponse->preavisDepartTransmis = $signalement->getisPreavisDepart();
        $signalementResponse->demandeRelogementEffectuee = $signalement->getIsRelogement();
        $signalementResponse->loyersPayes = $this->stringToBool($signalement->getInformationComplementaire()?->getinformationsComplementairesSituationOccupantsLoyersPayes());
        $signalementResponse->dateEffetBail = $signalement->getInformationComplementaire()?->getInformationsComplementairesSituationBailleurDateEffetBail() ?? $signalementResponse->dateEntreeLogement;

        $signalementResponse->score = $signalement->getScore();
        $signalementResponse->scoreBatiment = $signalement->getScoreBatiment();
        $signalementResponse->scoreLogement = $signalement->getScoreLogement();

        $signalementResponse->desordres = $this->buildDesordres($signalement);
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
            if (InterventionType::ARRETE_PREFECTORAL !== $intervention->getType()) {
                $signalementResponse->visites[] = $this->visiteFactory->createInstance($intervention);
            }
        }
        foreach ($signalement->getFiles() as $file) {
            $signalementResponse->files[] = $this->fileFactory->createFrom($file);
        }
        // divers
        $signalementResponse->territoireNom = $signalement->getTerritory()?->getName();
        $signalementResponse->territoireCode = $signalement->getTerritory()?->getZip();
        $signalementResponse->signalementImporte = $signalement->getIsImported();

        $signalementResponse->adresse = new Adresse(
            adresse: $signalement->getAdresseOccupant(),
            codePostal: $signalement->getCpOccupant(),
            ville: $signalement->getVilleOccupant(),
            etage: $signalement->getEtageOccupant(),
            escalier: $signalement->getEscalierOccupant(),
            numAppart: $signalement->getNumAppartOccupant(),
            codeInsee: $signalement->getInseeOccupant(),
            latitude: $signalement->getGeoloc()['lat'] ?? null,
            longitude: $signalement->getGeoloc()['lng'] ?? null,
            adresseAutre: $signalement->getAdresseAutreOccupant(),
            rnbId: $signalement->getRnbIdOccupant(),
            cleBanAdresse: $signalement->getBanIdOccupant(),
        );

        foreach (self::PERSONNE_TYPES as $personneType) {
            if (($personne = $this->createPersonne($signalement, $personneType)) !== null) {
                $signalementResponse->personnes[] = $personne;
            }
        }

        return $signalementResponse;
    }

    private function stringToBool(?string $value): bool|string|null
    {
        if (in_array($value, ['oui', 'piece_unique'])) {
            return true;
        }
        if (in_array($value, ['non', 'plusieurs_pieces'])) {
            return false;
        }

        if ('nsp' === $value) {
            return $value;
        }

        return null;
    }

    private function stringToInt(?string $value): ?int
    {
        if (!empty($value)) {
            return (int) $value;
        }

        return null;
    }

    private function createPersonne(Signalement $signalement, PersonneType $personneType): ?Personne
    {
        if (PersonneType::OCCUPANT === $personneType) {
            return new Personne(
                personneType: $personneType,
                civilite: $signalement->getCiviliteOccupant(),
                nom: $signalement->getNomOccupant(),
                prenom: $signalement->getPrenomOccupant(),
                email: $signalement->getMailOccupant(),
                telephone: $signalement->getTelOccupant(),
                telephoneSecondaire: $signalement->getTelOccupantBis(),
                dateNaissance: $signalement->getDateNaissanceOccupant()?->format('Y-m-d') ?? $signalement->getInformationComplementaire()?->getInformationsComplementairesSituationOccupantsDateNaissance(),
                revenuFiscal: $signalement->getInformationComplementaire()?->getInformationsComplementairesSituationOccupantsRevenuFiscal(),
                beneficiaireRsa: $this->stringToBool($signalement->getInformationComplementaire()?->getInformationsComplementairesSituationOccupantsBeneficiaireRsa()),
                beneficiaireFsl: $this->stringToBool($signalement->getInformationComplementaire()?->getInformationsComplementairesSituationOccupantsBeneficiaireFsl()),
                allocataire: in_array($signalement->getIsAllocataire(), [null, '']) ? null : (bool) $signalement->getIsAllocataire(), // valeurs possibles : null, '', 0, 1, 'CAF', 'MSA',
                typeAllocataire: in_array($signalement->getIsAllocataire(), ['MSA', 'CAF']) ? $signalement->getIsAllocataire() : null,
                numAllocataire: $signalement->getNumAllocataire(),
                montantAllocation: $signalement->getSituationFoyer()?->getLogementSocialMontantAllocation() ?? $signalement->getMontantAllocation(),
            );
        }

        if (PersonneType::DECLARANT === $personneType && !empty($signalement->getLienDeclarantOccupant())) {
            return new Personne(
                personneType: $personneType,
                structure: $signalement->getStructureDeclarant(),
                lienOccupant: $signalement->getLienDeclarantOccupant(),
                precisionTypeSiBailleur: $signalement->getTypeProprio(),
                estTravailleurSocialPourOccupant: $this->stringToBool($signalement->getSituationFoyer()?->getTravailleurSocialAccompagnementDeclarant()),
                nom: $signalement->getNomDeclarant(),
                prenom: $signalement->getPrenomDeclarant(),
                email: $signalement->getMailDeclarant(),
                telephone: $signalement->getTelDeclarant(),
                telephoneSecondaire: $signalement->getTelDeclarantSecondaire()
            );
        }

        if (PersonneType::PROPRIETAIRE === $personneType && !empty($signalement->getNomProprio())) {
            $adresse = new Adresse(
                adresse: $signalement->getAdresseProprio(),
                codePostal: $signalement->getCodePostalProprio(),
                ville: $signalement->getVilleProprio(),
            );

            return new Personne(
                personneType: $personneType,
                precisionTypeSiBailleur: $signalement->getTypeProprio(),
                structure: ProprioType::ORGANISME_SOCIETE === $signalement->getTypeProprio() ? $signalement->getDenominationProprio() : '',
                nom: $signalement->getNomProprio(),
                prenom: $signalement->getPrenomProprio(),
                email: $signalement->getMailProprio(),
                telephone: $signalement->getTelProprio(),
                telephoneSecondaire: $signalement->getTelProprioSecondaire(),
                dateNaissance: $signalement->getInformationComplementaire()?->getInformationsComplementairesSituationBailleurDateNaissance(),
                revenuFiscal: $signalement->getInformationComplementaire()?->getInformationsComplementairesSituationBailleurRevenuFiscal(),
                beneficiaireRsa: $this->stringToBool($signalement->getInformationComplementaire()?->getInformationsComplementairesSituationBailleurBeneficiaireRsa()),
                beneficiaireFsl: $this->stringToBool($signalement->getInformationComplementaire()?->getInformationsComplementairesSituationBailleurBeneficiaireFsl()),
                adresse: $adresse
            );
        }

        if (PersonneType::AGENCE === $personneType && !empty($signalement->getDenominationAgence()) && $this->parameterBag->get('feature_bo_signalement_create')) {
            $adresse = new Adresse(
                adresse: $signalement->getAdresseAgence(),
                codePostal: $signalement->getCodePostalAgence(),
                ville: $signalement->getVilleAgence(),
            );

            return new Personne(
                personneType: $personneType,
                structure: $signalement->getDenominationAgence(),
                nom: $signalement->getNomAgence(),
                prenom: $signalement->getPrenomAgence(),
                email: $signalement->getMailAgence(),
                telephone: $signalement->getTelAgence(),
                telephoneSecondaire: $signalement->getTelAgenceSecondaire(),
                adresse: $adresse
            );
        }

        return null;
    }

    private function buildDesordres(Signalement $signalement): array
    {
        $desordres = [];
        $desordresInfos = $this->signalementDesordresProcessor->process($signalement);
        if (!$signalement->isV2()) {
            foreach ($desordresInfos['criticitesArranged'] as $label => $data) {
                $desordres[] = new Desordre($label, $data);
            }
        } else {
            foreach (DesordreCritereZone::getLabelList() as $zone => $unused) {
                if (isset($desordresInfos['criticitesArranged'][$zone])) {
                    foreach ($desordresInfos['criticitesArranged'][$zone] as $label => $data) {
                        $desordres[] = new Desordre($label, $data, $zone);
                    }
                }
            }
        }

        return $desordres;
    }
}
