<?php

namespace App\Factory\Esabora;

use App\Entity\Affectation;
use App\Entity\Signalement;
use App\Messenger\Message\DossierMessageSISH;
use App\Service\Esabora\AddressParser;
use App\Service\Esabora\Enum\PersonneType;

class DossierMessageSISHFactory
{
    public function __construct(private readonly AddressParser $addressParser)
    {
    }

    public function createInstance(Affectation $affectation): DossierMessageSISH
    {
        $signalement = $affectation->getSignalement();
        $partner = $affectation->getPartner();

        list($numero, $extension, $adresse) = $this->addressParser->parse($signalement->getAdresseOccupant());

        return (new DossierMessageSISH())
            ->setUrl($partner->getEsaboraUrl())
            ->setToken($partner->getEsaboraToken())
            ->setPartnerId($partner->getId())
            ->setSignalementId($signalement->getId())
            ->setReferenceAdresse($signalement->getUuid())
            ->setLocalisationNumero($numero)
            ->setLocalisationNumeroExt($extension)
            ->setLocalisationAdresse1($adresse)
            ->setLocalisationAdresse2($signalement->getAdresseAutreOccupant())
            ->setLocalisationCodePostal($signalement->getCpOccupant())
            ->setLocalisationVille($signalement->getVilleOccupant())
            ->setLocalisationLocalisationInsee($signalement->getInseeOccupant())
            ->setSasLogicielProvenance('H')
            ->setReferenceDossier($signalement->getUuid())
            ->setSasDateAffectation($affectation->getCreatedAt()->format('d/m/Y H:i'))
            ->setLocalisationEtage($signalement->getEtageOccupant())
            ->setLocalisationEscalier($signalement->getEscalierOccupant())
            ->setLocalisationNumPorte($signalement->getNumAppartOccupant())
            ->setSitOccupantNbAdultes($signalement->getNbAdultes())
            ->setSitOccupantNbEnfantsM6($signalement->getNbEnfantsM6())
            ->setSitOccupantNbEnfantsP6($signalement->getNbEnfantsP6())
            ->setSitOccupantNbOccupants($signalement->getNbOccupantsLogement())
            ->setSitOccupantNumAllocataire($signalement->getNumAllocataire())
            ->setSitOccupantMontantAlloc($signalement->getMontantAllocation())
            ->setSitLogementBailEncours((int) $signalement->getIsBailEnCours())
            ->setSitLogementBailDateEntree($signalement->getDateEntree()->format('d/m/Y'))
            ->setSitLogementPreavisDepart((int) $signalement->getIsPreavisDepart())
            ->setSitLogementRelogement((int) $signalement->getIsRelogement())
            ->setSitLogementSuperficie($signalement->getSuperficie())
            ->setSitLogementMontantLoyer($signalement->getLoyer())
            ->setDeclarantNonOccupant($signalement->getIsNotOccupant())
            ->setLogementNature($signalement->getNatureLogement())
            ->setLogementType($signalement->getTypeLogement())
            ->setLogementSocial($signalement->getIsLogementSocial())
            ->setLogementAnneeConstruction($signalement->getAnneeConstruction())
            ->setLogementTypeEnergie($signalement->getTypeEnergieLogement())
            ->setLogementCollectif((int) $signalement->getIsLogementCollectif())
            ->setLogementAvant1949((int) $signalement->getIsConstructionAvant1949())
            ->setLogementDiagST((int) $signalement->getIsDiagSocioTechnique())
            ->setLogementInvariant($signalement->getNumeroInvariant())
            ->setLogementNbPieces($signalement->getNbPiecesLogement())
            ->setLogementNbChambres($signalement->getNbChambresLogement())
            ->setLogementNbNiveaux($signalement->getNbNiveauxLogement())
            ->setProprietaireAverti((int) $signalement->getIsProprioAverti())
            ->setProprietaireAvertiDate($signalement->getProprioAvertiAt()->format('d/m/Y'))
            ->setProprietaireAvertiMoyen($signalement->getModeContactProprio())
            ->setSignalementScore($signalement->getScore())
            ->setSignalementOrigine($signalement->getOrigineSignalement())
            ->setSignalementNumero($signalement->getReference())
            ->setSignalementCommentaire()
            ->setSignalementDate($signalement->getCreatedAt()->format('Y-m-d'))
            ->setSignalementDetails()
            ->setSignalementProblemes()
            ->setPiecesJointesObservation()
            ->addPersonne($this->createDossierPersonne($signalement, PersonneType::OCCUPANT))
            ->addPersonne($this->createDossierPersonne($signalement, PersonneType::DECLARANT))
            ->addPersonne($this->createDossierPersonne($signalement, PersonneType::PROPRIETAIRE));
    }

    public function createDossierPersonne(Signalement $signalement, PersonneType $personneType): ?DossierMessageSISHPersonne
    {
        return match ($personneType) {
            PersonneType::OCCUPANT => (new DossierMessageSISHPersonne())
                ->setType(PersonneType::OCCUPANT->value)
                ->setNom($signalement->getNomOccupant())
                ->setPrenom($signalement->getPrenomOccupant())
                ->setEmail($signalement->getMailOccupant())
                ->setTelephone($signalement->getTelOccupant()),
            PersonneType::PROPRIETAIRE => (new DossierMessageSISHPersonne())
                ->setType(PersonneType::PROPRIETAIRE->value)
                ->setNom($signalement->getNomProprio())
                ->setAdresse($signalement->getAdresseProprio())
                ->setEmail($signalement->getMailProprio())
                ->setTelephone($signalement->getTelProprio()),
            PersonneType::DECLARANT => (new DossierMessageSISHPersonne())
                ->setType(PersonneType::DECLARANT->value)
                ->setNom($signalement->getNomDeclarant())
                ->setPrenom($signalement->getPrenomDeclarant())
                ->setEmail($signalement->getMailDeclarant())
                ->setTelephone($signalement->getTelDeclarant())
                ->setStructure($signalement->getStructureDeclarant())
                ->setLienOccupant($signalement->getLienDeclarantOccupant()),
            default => null,
        };
    }
}
