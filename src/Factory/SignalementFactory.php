<?php

namespace App\Factory;

use App\Dto\ServiceSecours\FormServiceSecours;
use App\Entity\Enum\CreationSource;
use App\Entity\Enum\EtageType;
use App\Entity\Enum\MotifCloture;
use App\Entity\Enum\ProfileDeclarant;
use App\Entity\Enum\SignalementStatus;
use App\Entity\Model\TypeCompositionLogement;
use App\Entity\ServiceSecoursRoute;
use App\Entity\Signalement;
use App\Entity\Territory;
use App\Service\Signalement\ZipcodeProvider;

class SignalementFactory
{
    public function __construct(
        private ZipcodeProvider $zipcodeProvider,
    ) {
    }

    public function createInstanceFromFormServiceSecours(FormServiceSecours $formServiceSecours, ServiceSecoursRoute $serviceSecoursRoute): Signalement
    {
        $signalement = new Signalement();
        $typeCompositionLogement = new TypeCompositionLogement();

        // default data
        $signalement->setProfileDeclarant(ProfileDeclarant::SERVICE_SECOURS);
        $signalement->setIsCguAccepted(true);
        $signalement->setCreationSource(CreationSource::FORM_SERVICE_SECOURS);
        // data calculated from serviceSecoursRoute
        $signalement->setServiceSecours($serviceSecoursRoute);
        $signalement->setStructureDeclarant($serviceSecoursRoute->getName());
        $signalement->setMailDeclarant($serviceSecoursRoute->getEmail());
        $signalement->setTelDeclarant($serviceSecoursRoute->getPhone());

        // data from step1
        $signalement->setMatriculeDeclarant($formServiceSecours->step1->matriculeDeclarant);
        $signalement->setNomDeclarant($formServiceSecours->step1->nomDeclarant);
        $signalement->setDateMissionServiceSecours($formServiceSecours->step1->dateMission);
        $signalement->setOrigineMissionServiceSecours($formServiceSecours->step1->origineMission);
        $signalement->setOrdreMissionServiceSecours($formServiceSecours->step1->ordreMission);

        // data from step2
        $signalement->setAdresseOccupant($formServiceSecours->step2->adresseOccupant)
            ->setCpOccupant($formServiceSecours->step2->cpOccupant)
            ->setVilleOccupant($formServiceSecours->step2->villeOccupant)
            ->setInseeOccupant($formServiceSecours->step2->inseeOccupant)
            ->setAdresseAutreOccupant($formServiceSecours->step2->adresseAutreOccupant)
            ->setNatureLogement($formServiceSecours->step2->natureLogement);

        switch ($formServiceSecours->step2->isLogementSocial) {
            case 'oui':
                $signalement->setIsLogementSocial(true);
                break;
            case 'non':
                $signalement->setIsLogementSocial(false);
                break;
            case 'nsp':
                $signalement->setIsLogementSocial(null);
                break;
        }

        // Déterminer le territoire : priorité au code INSEE, sinon utiliser le code postal
        $territory = null;
        if (!empty($formServiceSecours->step2->inseeOccupant)) {
            $territory = $this->zipcodeProvider->getTerritoryByInseeCode($formServiceSecours->step2->inseeOccupant);
        }
        if (!$territory && !empty($formServiceSecours->step2->cpOccupant)) {
            $territory = $this->zipcodeProvider->getTerritoryByPostalCode($formServiceSecours->step2->cpOccupant);
        }
        $signalement->setTerritory($territory);

        if (empty($formServiceSecours->step2->inseeOccupant)) {
            $signalement->setManualAddressOccupant(true);
        }

        if ('appartement' === $signalement->getNatureLogement()) {
            /** @var EtageType $appartementEtage */
            $appartementEtage = $formServiceSecours->step2->typeEtageLogement;
            if (!empty($appartementEtage)) {
                switch ($appartementEtage) {
                    case EtageType::RDC:
                        $typeCompositionLogement->setTypeLogementRdc('oui')
                            ->setTypeLogementDernierEtage('non');
                        break;
                    case EtageType::DERNIER_ETAGE:
                        $typeCompositionLogement->setTypeLogementDernierEtage('oui')
                            ->setTypeLogementRdc('non');
                        break;
                    case EtageType::SOUSSOL:
                        // On n'a pas de champ juste "sous-sol", on a "sous-sol sans fenêtre"
                        $signalement->setEtageOccupant('-1');
                        $typeCompositionLogement->setTypeLogementRdc('non')
                            ->setTypeLogementDernierEtage('non');
                        break;
                    case EtageType::AUTRE:
                        $etageOccupant = $formServiceSecours->step2->etageOccupant;
                        if (!empty($etageOccupant)) {
                            $signalement->setEtageOccupant($etageOccupant);
                        }
                        $typeCompositionLogement->setTypeLogementRdc('non')
                            ->setTypeLogementDernierEtage('non');
                        break;
                }
            }
        } elseif ('autre' === $signalement->getNatureLogement()) {
            $typeCompositionLogement->setTypeLogementNatureAutrePrecision($formServiceSecours->step2->natureLogementAutre);
        }

        $signalement->setNbPiecesLogement((int) $formServiceSecours->step2->nbPiecesLogement)
            ->setSuperficie((int) $formServiceSecours->step2->superficie);

        $typeCompositionLogement->setCompositionLogementSuperficie($formServiceSecours->step2->superficie);

        $signalement->setTypeCompositionLogement($typeCompositionLogement);

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
