<?php

namespace App\Factory;

use App\Dto\ServiceSecours\FormServiceSecours;
use App\Dto\ServiceSecours\FormServiceSecoursStep5;
use App\Entity\DesordreCritere;
use App\Entity\Enum\CreationSource;
use App\Entity\Enum\EtageType;
use App\Entity\Enum\ProfileDeclarant;
use App\Entity\Enum\ProfileOccupant;
use App\Entity\Model\TypeCompositionLogement;
use App\Entity\ServiceSecoursRoute;
use App\Entity\Signalement;
use App\Repository\BailleurRepository;
use App\Repository\DesordreCritereRepository;
use App\Service\Signalement\SignalementAddressUpdater;
use App\Service\Signalement\ZipcodeProvider;

class SignalementServiceSecoursFactory
{
    public function __construct(
        private readonly ZipcodeProvider $zipcodeProvider,
        private readonly SignalementAddressUpdater $signalementAddressUpdater,
        private readonly BailleurRepository $bailleurRepository,
        private readonly DesordreCritereRepository $desordreCritereRepository,
    ) {
    }

    public function create(
        FormServiceSecours $formServiceSecours,
        ServiceSecoursRoute $serviceSecoursRoute,
    ): Signalement {
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

        $this->handleStep1($formServiceSecours, $signalement);
        $this->handleStep2($formServiceSecours, $signalement, $typeCompositionLogement);
        $this->handleStep3($formServiceSecours, $signalement, $typeCompositionLogement);
        $this->handleStep4($formServiceSecours, $signalement);
        $this->handleStep5($formServiceSecours, $signalement);
        $signalement->setTypeCompositionLogement($typeCompositionLogement);

        return $signalement;
    }

    private function handleStep1(FormServiceSecours $formServiceSecours, Signalement $signalement): void
    {
        $signalement->setMatriculeDeclarant($formServiceSecours->step1->matriculeDeclarant);
        $signalement->setNomDeclarant($formServiceSecours->step1->nomDeclarant);
        $signalement->setDateMissionServiceSecours($formServiceSecours->step1->dateMission);
        $signalement->setOrigineMissionServiceSecours($formServiceSecours->step1->origineMission);
        $signalement->setOrdreMissionServiceSecours($formServiceSecours->step1->ordreMission);
    }

    private function handleStep2(
        FormServiceSecours $formServiceSecours,
        Signalement $signalement,
        TypeCompositionLogement $typeCompositionLogement,
    ): void {
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

        $isLogementSocial = $formServiceSecours->step2->isLogementSocial;
        // case 'nsp' si null, which is the default value of the entity
        if ('oui' === $isLogementSocial) {
            $signalement->setIsLogementSocial(true);
        } elseif ('non' === $isLogementSocial) {
            $signalement->setIsLogementSocial(false);
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
                    default:
                        // Alerte sonar - cas théoriquement pas possible
                        break;
                }
            }
        } elseif ('autre' === $signalement->getNatureLogement()) {
            $typeCompositionLogement->setTypeLogementNatureAutrePrecision($formServiceSecours->step2->natureLogementAutre);
        }

        $signalement
            ->setNbPiecesLogement((int) $formServiceSecours->step2->nbPiecesLogement)
            ->setSuperficie((int) $formServiceSecours->step2->superficie);
    }

    private function handleStep3(
        FormServiceSecours $formServiceSecours,
        Signalement $signalement,
        TypeCompositionLogement $typeCompositionLogement,
    ): void {
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
    }

    private function handleStep4(
        FormServiceSecours $formServiceSecours,
        Signalement $signalement,
    ): void {
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
        $signalement->setDenominationSyndic($formServiceSecours->step4->denominationSyndic);
        $signalement->setNomSyndic($formServiceSecours->step4->nomSyndic);
        $signalement->setMailSyndic($formServiceSecours->step4->mailSyndic);
        $signalement->setTelSyndic($formServiceSecours->step4->telSyndic);
        $signalement->setTelSyndicSecondaire($formServiceSecours->step4->telSyndicSecondaire);
    }

    private function handleStep5(FormServiceSecours $formServiceSecours, Signalement $signalement): void
    {
        $jsonContent = [];

        $desordresCriteres = $this->desordreCritereRepository->findBySlugsWithPrecisions(
            $formServiceSecours->step5->desordres
        );

        /** @var DesordreCritere $desordreCritere */
        foreach ($desordresCriteres as $desordreCritere) {
            $desordrePrecision = $desordreCritere->getDesordrePrecisions()->first();
            if (false === $desordrePrecision) {
                continue;
            }
            $signalement->addDesordrePrecision($desordrePrecision);

            if (FormServiceSecoursStep5::DESORDRE_AUTRE_PRECISION_SLUG === $desordrePrecision->getDesordrePrecisionSlug()) {
                $jsonContent[$desordrePrecision->getDesordrePrecisionSlug()] = $formServiceSecours->step5->desordresAutre;
            }
        }

        if (!empty($uploadedFiles = $formServiceSecours->step5->uploadedFiles)) {
            $jsonContent['uploadedFiles'] = array_map(
                static fn (string $file) => array_merge(json_decode($file, true), ['slug' => 'desordres_service_secours']),
                $uploadedFiles
            );
        }
        $signalement->setJsonContent($jsonContent);
        $signalement->setAutresOccupantsDesordre($formServiceSecours->step5->autresOccupantsDesordre);
    }
}
