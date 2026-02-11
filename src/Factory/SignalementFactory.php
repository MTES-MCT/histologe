<?php

namespace App\Factory;

use App\Dto\ServiceSecours\FormServiceSecours;
use App\Entity\Enum\CreationSource;
use App\Entity\Enum\MotifCloture;
use App\Entity\Enum\ProfileDeclarant;
use App\Entity\Enum\SignalementStatus;
use App\Entity\ServiceSecoursRoute;
use App\Entity\Signalement;
use App\Entity\Territory;

class SignalementFactory
{
    public function createInstanceFromFormServiceSecours(FormServiceSecours $formServiceSecours, ServiceSecoursRoute $serviceSecoursRoute): Signalement
    {
        $signalement = new Signalement();
        // default data
        $signalement->setProfileDeclarant(ProfileDeclarant::SERVICE_SECOURS);
        $signalement->setIsCguAccepted(true);
        // TODO : $signalement->setCreationSource(CreationSource::FORM_SERVICE_SECOURS);
        // data calculated from serviceSecoursRoute
        $signalement->setServiceSecours($serviceSecoursRoute);
        $signalement->setStructureDeclarant($serviceSecoursRoute->getName());
        $signalement->setMailDeclarant($serviceSecoursRoute->getEmail());
        $signalement->setTelDeclarant($serviceSecoursRoute->getPhone());
        // data from step1
        $signalement->setMatriculeDeclarant($formServiceSecours->step1->matriculeDeclarant);
        $signalement->setNomDeclarant($formServiceSecours->step1->nomDeclarant);
        $signalement->setDateMission($formServiceSecours->step1->dateMission);
        $signalement->setOrigineMission($formServiceSecours->step1->origineMission);
        $signalement->setOrdreMission($formServiceSecours->step1->ordreMission);

        // TODO : manage other steps
        //
        //
        return $signalement;
    }

    /**
     * @param array<string, mixed> $data
     */
    public function createInstanceFromArrayForImport(Territory $territory, array $data): Signalement
    {
        if (empty($data['statut'])) {
            $data['statut'] = SignalementStatus::ACTIVE;
            if ($data['motifCloture'] || $data['closedAt']) {
                $data['statut'] = SignalementStatus::CLOSED;
            }
        }

        return (new Signalement())
            ->setIsImported(true)
            ->setCreationSource(CreationSource::IMPORT)
            ->setTerritory($territory)
            ->setDetails($data['details'])
            ->setIsProprioAverti((bool) $data['isProprioAverti'])
            ->setNbAdultes($data['nbAdultes'])
            ->setNbEnfantsM6($data['nbEnfantsM6'])
            ->setNbEnfantsP6($data['nbEnfantsP6'])
            ->setIsAllocataire($data['isAllocataire'])
            ->setNumAllocataire($data['numAllocataire'])
            ->setNatureLogement($data['natureLogement'])
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
            ->setStatut($data['statut'])
            ->setValidatedAt(
                SignalementStatus::ACTIVE === $data['statut'] ? $data['createdAt'] : new \DateTimeImmutable()
            )
            ->setReference($data['reference'])
            ->setMontantAllocation((float) $data['montantAllocation'])
            ->setCodeProcedure($data['codeProcedure'])
            ->setEtageOccupant($data['etageOccupant'])
            ->setEscalierOccupant($data['escalierOccupant'])
            ->setNumAppartOccupant($data['numAppartOccupant'])
            ->setAdresseAutreOccupant($data['adresseAutreOccupant'])
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
            ->setMotifCloture(
                null !== $data['motifCloture']
                ? MotifCloture::tryFrom($data['motifCloture'])
                : null)
            ->setClosedAt($data['closedAt'])
            ->setIsFondSolidariteLogement((bool) $data['isFondSolidariteLogement']);
    }
}
