<?php

namespace App\Factory\Esabora;

use App\Entity\Affectation;
use App\Entity\Enum\PartnerType;
use App\Entity\Signalement;
use App\Entity\Suivi;
use App\Messenger\Message\DossierMessageSISH;
use App\Service\Esabora\AddressParser;
use App\Service\Esabora\Enum\PersonneType;
use App\Service\Esabora\Model\DossierMessageSISHPersonne;
use App\Service\UploadHandlerService;

class DossierMessageSISHFactory extends AbstractDossierMessageFactory
{
    public function __construct(
        private readonly AddressParser $addressParser,
        private readonly UploadHandlerService $uploadHandlerService
    ) {
        parent::__construct($this->uploadHandlerService);
    }

    public function supports(Affectation $affectation): bool
    {
        return PartnerType::ARS === $affectation->getPartner()->getType();
    }

    public function createInstance(Affectation $affectation): DossierMessageSISH
    {
        $signalement = $affectation->getSignalement();
        $partner = $affectation->getPartner();

        $address = $this->addressParser->parse($signalement->getAdresseOccupant());
        /** @var Suivi $firstSuivi */
        $firstSuivi = $signalement->getSuivis()->first();

        return (new DossierMessageSISH())
            ->setUrl($partner->getEsaboraUrl())
            ->setToken($partner->getEsaboraToken())
            ->setPartnerId($partner->getId())
            ->setPartnerType($partner->getType()->value)
            ->setSignalementId($signalement->getId())
            ->setReferenceAdresse($signalement->getUuid())
            ->setLocalisationNumero($address['number'] ?? null)
            ->setLocalisationNumeroExt($address['suffix'] ?? null)
            ->setLocalisationAdresse1($address['street'] ?? null)
            ->setLocalisationAdresse2($signalement->getAdresseAutreOccupant())
            ->setLocalisationCodePostal($signalement->getCpOccupant())
            ->setLocalisationVille($signalement->getVilleOccupant())
            ->setLocalisationLocalisationInsee($signalement->getInseeOccupant())
            ->setSasLogicielProvenance('H')
            ->setReferenceDossier($signalement->getUuid())
            ->setSasDateAffectation($affectation->getCreatedAt()?->format('d/m/Y H:i'))
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
            ->setSitLogementBailDateEntree($signalement->getDateEntree()?->format('d/m/Y'))
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
            ->setProprietaireAvertiDate($signalement->getProprioAvertiAt()?->format('d/m/Y'))
            ->setProprietaireAvertiMoyen(implode(',', $signalement->getModeContactProprio()))
            ->setSignalementScore($signalement->getScore())
            ->setSignalementOrigine($signalement->getOrigineSignalement())
            ->setSignalementNumero($signalement->getReference())
            ->setSignalementCommentaire($firstSuivi->getDescription())
            ->setSignalementDate($signalement->getCreatedAt()?->format('Y-m-d'))
            ->setSignalementDetails($signalement->getDetails())
            ->setSignalementProblemes($this->buildProblemes($signalement))
            ->setPiecesJointesObservation($this->buildPiecesJointes($signalement))
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

    private function buildProblemes(Signalement $signalement): string
    {
        $commentaire = null;
        foreach ($signalement->getCriticites() as $criticite) {
            $commentaire = $criticite->getCritere()->getLabel().' => Etat '.$criticite->getScoreLabel().'\n';
        }

        return $commentaire;
    }
}
