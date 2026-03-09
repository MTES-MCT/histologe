<?php

namespace App\Factory;

use App\Dto\ServiceSecours\FormServiceSecours;
use App\Entity\Enum\CreationSource;
use App\Entity\Enum\EtageType;
use App\Entity\Enum\MotifCloture;
use App\Entity\Enum\ProfileDeclarant;
use App\Entity\Enum\ProfileOccupant;
use App\Entity\Enum\SignalementStatus;
use App\Entity\Model\TypeCompositionLogement;
use App\Entity\ServiceSecoursRoute;
use App\Entity\Signalement;
use App\Entity\Territory;
use App\Repository\BailleurRepository;
use App\Service\Signalement\SignalementAddressUpdater;
use App\Service\Signalement\ZipcodeProvider;

class SignalementFactory
{
    public function __construct(
        private readonly ZipcodeProvider $zipcodeProvider,
        private readonly SignalementAddressUpdater $signalementAddressUpdater,
        private BailleurRepository $bailleurRepository,
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
            ->setAdresseAutreOccupant($formServiceSecours->step2->adresseAutreOccupant)
            ->setNatureLogement($formServiceSecours->step2->natureLogement);

        if (empty($formServiceSecours->step2->inseeOccupant)) {
            $signalement->setManualAddressOccupant(true);
        } else {
            $signalement->setInseeOccupant($formServiceSecours->step2->inseeOccupant);
        }

        switch ($formServiceSecours->step2->isLogementSocial) {
            case 'oui':
                $signalement->setIsLogementSocial(true);
                break;
            case 'non':
                $signalement->setIsLogementSocial(false);
                break;
                // case 'nsp' si null, which is the default value of the entity
        }

        $this->signalementAddressUpdater->updateAddressOccupantFromBanData(signalement: $signalement);

        $signalement->setTerritory(
            territory: $this->zipcodeProvider->getTerritoryByInseeCode($signalement->getInseeOccupant())
        );

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

        // data from step3
        $profilOccupant = $formServiceSecours->step3->profilOccupant;
        if ('logement_vacant' === $profilOccupant) {
            $signalement->setIsLogementVacant(true);
        } elseif ('indetermine' === $profilOccupant) {
            // nothing to set on signalement
        } else {
            $signalement->setIsLogementVacant(false);
            $signalement->setProfileOccupant(ProfileOccupant::from($profilOccupant));
        }
        $signalement->setNomOccupant($formServiceSecours->step3->nomOccupant);
        $signalement->setPrenomOccupant($formServiceSecours->step3->prenomOccupant);
        $signalement->setMailOccupant($formServiceSecours->step3->mailOccupant);
        $signalement->setTelOccupant($formServiceSecours->step3->telOccupant);
        $nbPersonnes = (int) $formServiceSecours->step3->nbAdultesDansLogement + (int) $formServiceSecours->step3->nbEnfantsDansLogement;
        $signalement->setNbOccupantsLogement($nbPersonnes);
        $typeCompositionLogement->setCompositionLogementNombreEnfants($formServiceSecours->step3->nbEnfantsDansLogement);
        $typeCompositionLogement->setCompositionLogementEnfants($formServiceSecours->step3->isEnfantsMoinsSixAnsDansLogement);
        $signalement->setAutreSituationVulnerabilite($formServiceSecours->step3->autreVulnerabilite);

        // data from step4
        if ('oui' === $formServiceSecours->step4->isBailleurAverti) {
            $signalement->setIsProprioAverti(true);
        } elseif ('non' === $formServiceSecours->step4->isBailleurAverti) {
            $signalement->setIsProprioAverti(false);
        }
        $signalement->setDenominationProprio($formServiceSecours->step4->denominationProprio);
        if ($signalement->getDenominationProprio()) {
            $bailleur = $this->bailleurRepository->findOneBailleurBy(name: $signalement->getDenominationProprio(), territory: $signalement->getTerritory());
            $signalement->setBailleur($bailleur);
        }
        $signalement->setNomProprio($formServiceSecours->step4->nomProprio);
        $signalement->setPrenomProprio($formServiceSecours->step4->prenomProprio);
        $signalement->setMailProprio($formServiceSecours->step4->mailProprio);
        $signalement->setTelProprio($formServiceSecours->step4->telProprio);
        $signalement->setDenominationAgence($formServiceSecours->step4->denominationAgence);
        $signalement->setNomAgence($formServiceSecours->step4->nomAgence);
        $signalement->setMailAgence($formServiceSecours->step4->mailAgence);
        $signalement->setTelAgence($formServiceSecours->step4->telAgence);

        // TODO : manage other steps
        //
        //
        $signalement->setTypeCompositionLogement($typeCompositionLogement);

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
