<?php

namespace App\Factory;

use App\Entity\Signalement;
use App\Entity\Territory;

class SignalementFactory
{
    public function createInstanceFrom(Territory $territory, array $data, bool $isImported = false): Signalement
    {
        if (empty($data['statut'])) {
            $data['statut'] = Signalement::STATUS_ACTIVE;
            if ($data['motifCloture'] || $data['closedAt']) {
                $data['statut'] = Signalement::STATUS_CLOSED;
            }
        }

        return (new Signalement())
            ->setIsImported($isImported)
            ->setTerritory($territory)
            ->setDetails($data['details'])
            ->setIsProprioAverti((bool) $data['isProprioAverti'])
            ->setNbAdultes($data['nbAdultes'])
            ->setNbEnfantsM6($data['nbEnfantsM6'])
            ->setNbEnfantsP6($data['nbEnfantsP6'])
            ->setIsAllocataire($data['isAllocataire'])
            ->setNumAllocataire($data['numAllocataire'])
            ->setNatureLogement($data['natureLogement'])
            ->setTypeLogement($data['typeLogement'])
            ->setSuperficie($data['superficie'])
            ->setLoyer($data['loyer'])
            ->setIsBailEnCours((bool) $data['isBailEnCours'])
            ->setDateEntree($data['dateEntree'])
            ->setNomProprio($data['nomProprio'])
            ->setAdresseProprio($data['adresseProprio'])
            ->setTelProprio($data['telProprio'])
            ->setMailProprio($data['mailProprio'])
            ->setIsLogementSocial((bool) $data['isLogementSocial'])
            ->setIsPreavisDepart((bool) $data['isPreavisDepart'])
            ->setIsRelogement((bool) $data['isRelogement'])
            ->setIsRefusIntervention($data['isRefusIntervention'])
            ->setRaisonRefusIntervention($data['raisonRefusIntervention'])
            ->setIsNotOccupant((bool) $data['isNotOccupant'])
            ->setNomDeclarant($data['nomDeclarant'])
            ->setPrenomDeclarant($data['prenomDeclarant'])
            ->setTelDeclarant($data['telDeclarant'])
            ->setMailDeclarant($data['mailDeclarant'])
            ->setStructureDeclarant($data['structureDeclarant'])
            ->setNomOccupant($data['nomOccupant'])
            ->setPrenomOccupant($data['prenomOccupant'])
            ->setTelOccupant($data['telOccupant'])
            ->setMailOccupant($data['mailOccupant'])
            ->setAdresseOccupant($data['adresseOccupant'])
            ->setCpOccupant($data['cpOccupant'])
            ->setVilleOccupant($data['villeOccupant'])
            ->setIsCguAccepted((bool) $data['isCguAccepted'])
            ->setCreatedAt($data['createdAt'])
            ->setModifiedAt($data['modifiedAt'])
            ->setStatut((int) $data['statut'])
            ->setValidatedAt(
                Signalement::STATUS_ACTIVE === $data['statut'] ? $data['createdAt'] : new \DateTimeImmutable()
            )
            ->setReference($data['reference'])
            ->setDateVisite($data['dateVisite'])
            ->setIsOccupantPresentVisite((bool) $data['isOccupantPresentVisite'])
            ->setMontantAllocation((float) $data['montantAllocation'])
            ->setCodeProcedure($data['codeProcedure'])
            ->setEtageOccupant($data['etageOccupant'])
            ->setEscalierOccupant($data['escalierOccupant'])
            ->setNumAppartOccupant($data['numAppartOccupant'])
            ->setAdresseAutreOccupant($data['adresseAutreOccupant'])
            ->setModeContactProprio($data['modeContactProprio'])
            ->setInseeOccupant($data['inseeOccupant'])
            ->setLienDeclarantOccupant($data['lienDeclarantOccupant'])
            ->setIsConsentementTiers((bool) $data['isConsentementTiers'])
            ->setIsRsa((bool) $data['isRsa'])
            ->setAnneeConstruction($data['anneeConstruction'])
            ->setTypeEnergieLogement($data['typeEnergieLogement'])
            ->setOrigineSignalement($data['origineSignalement'])
            ->setSituationOccupant($data['situationOccupant'])
            ->setSituationProOccupant($data['situationProOccupant'])
            ->setNaissanceOccupants($data['naissanceOccupants'])
            ->setIsLogementCollectif((bool) $data['isLogementCollectif'])
            ->setIsConstructionAvant1949((bool) $data['isConstructionAvant1949'])
            ->setIsRisqueSurOccupation((bool) $data['isRisqueSurOccupation'])
            ->setProprioAvertiAt($data['prorioAvertiAt'])
            ->setNomReferentSocial($data['nomReferentSocial'])
            ->setStructureReferentSocial($data['StructureReferentSocial'])
            ->setNumeroInvariant($data['numeroInvariant'])
            ->setNbPiecesLogement((int) $data['nbPiecesLogement'])
            ->setNbChambresLogement((int) $data['nbChambresLogement'])
            ->setNbNiveauxLogement((int) $data['nbNiveauxLogement'])
            ->setNbOccupantsLogement((int) $data['nbOccupantsLogement'])
            ->setMotifCloture($data['motifCloture'])
            ->setClosedAt($data['closedAt'])
            ->setIsFondSolidariteLogement((bool) $data['isFondSolidariteLogement']);
    }
}
