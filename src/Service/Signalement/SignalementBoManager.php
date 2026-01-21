<?php

namespace App\Service\Signalement;

use App\Entity\DesordreCritere;
use App\Entity\DesordrePrecision;
use App\Entity\Enum\EtageType;
use App\Entity\Enum\ProfileDeclarant;
use App\Entity\Enum\ProfileOccupant;
use App\Entity\Enum\SignalementStatus;
use App\Entity\Model\InformationComplementaire;
use App\Entity\Model\InformationProcedure;
use App\Entity\Model\SituationFoyer;
use App\Entity\Model\TypeCompositionLogement;
use App\Entity\Signalement;
use App\Entity\User;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;

class SignalementBoManager
{
    private User $user;

    public function __construct(
        private readonly Security $security,
        private readonly SignalementAddressUpdater $signalementAddressUpdater,
        private readonly PostalCodeHomeChecker $postalCodeHomeChecker,
        private readonly ReferenceGenerator $referenceGenerator,
    ) {
        /** @var User $user */
        $user = $this->security->getUser();
        $this->user = $user;
    }

    public function formAddressManager(FormInterface $form, Signalement $signalement): bool
    {
        $signalement->setIsLogementVacant($form->get('logementVacant')->getData());

        $profileDeclarant = ProfileDeclarant::tryFrom($form->get('profileDeclarant')->getData());
        $signalement->setProfileDeclarant($profileDeclarant);
        $signalement->setLienDeclarantOccupant($form->get('lienDeclarantOccupant')->getData());

        $profileOccupant = null;
        if (empty($profileOccupant)) {
            switch ($profileDeclarant) {
                case ProfileDeclarant::TIERS_PARTICULIER:
                case ProfileDeclarant::TIERS_PRO:
                case ProfileDeclarant::SERVICE_SECOURS:
                    $profileOccupant = ProfileOccupant::tryFrom($form->get('profileOccupant')->getData());
                    break;
                case ProfileDeclarant::BAILLEUR:
                case ProfileDeclarant::LOCATAIRE:
                    $profileOccupant = ProfileOccupant::LOCATAIRE;
                    break;
                case ProfileDeclarant::BAILLEUR_OCCUPANT:
                    $profileOccupant = ProfileOccupant::BAILLEUR_OCCUPANT;
                    break;
                default:
                    break;
            }
        }
        $signalement->setProfileOccupant($profileOccupant);

        $typeCompositionLogement = $signalement->getTypeCompositionLogement() ? clone $signalement->getTypeCompositionLogement() : new TypeCompositionLogement();
        $signalement->setNatureLogement($form->get('natureLogement')->getData());
        if ('autre' === $signalement->getNatureLogement()) {
            $typeCompositionLogement->setTypeLogementNatureAutrePrecision($form->get('natureLogementAutre')->getData());
        }
        $typeCompositionLogement->setCompositionLogementNombrePersonnes($form->get('nbOccupantsLogement')->getData());
        $typeCompositionLogement->setCompositionLogementNombreEnfants($form->get('nbEnfantsDansLogement')->getData());
        $typeCompositionLogement->setCompositionLogementEnfants($form->get('enfantsDansLogementMoinsSixAns')->getData());
        $signalement->setTypeCompositionLogement($typeCompositionLogement);
        $situationFoyer = $signalement->getSituationFoyer() ? clone $signalement->getSituationFoyer() : new SituationFoyer();

        if ('nsp' === $form->get('isLogementSocial')->getData()) {
            $signalement->setIsLogementSocial(null);
            $situationFoyer->setLogementSocialAllocation(null);
        } elseif ($form->get('isLogementSocial')->getData()) {
            $situationFoyer->setLogementSocialAllocation('oui');
        } else {
            $situationFoyer->setLogementSocialAllocation('non');
        }

        $informationProcedure = $signalement->getInformationProcedure() ? clone $signalement->getInformationProcedure() : new InformationProcedure();
        $informationComplementaire = $signalement->getInformationComplementaire() ? clone $signalement->getInformationComplementaire() : new InformationComplementaire();
        $signalement->setSituationFoyer($situationFoyer);
        $signalement->setInformationProcedure($informationProcedure);
        $signalement->setInformationComplementaire($informationComplementaire);

        $fieldAddress = 'adresseCompleteOccupant';
        if ($form->get('adresseCompleteOccupant')->getData()) {
            $signalement->setAdresseOccupant($form->get('adresseCompleteOccupant')->getData());
            $signalement->setCpOccupant(null);
            $signalement->setVilleOccupant('');
            $this->signalementAddressUpdater->updateAddressOccupantFromBanData($signalement);
            if (!$signalement->getBanIdOccupant()) {
                $form->get('adresseCompleteOccupant')->addError(new FormError('Veuillez saisir l\'adresse manuellement.'));

                return false;
            }
        } else {
            $fieldAddress = 'adresseOccupant';
            $this->signalementAddressUpdater->updateAddressOccupantFromBanData($signalement);
        }

        $territory = $this->postalCodeHomeChecker->getActiveTerritory($signalement->getInseeOccupant());
        if (!$territory) {
            $form->get($fieldAddress)->addError(new FormError('L\'adresse renseignée ne correspond pas à un territoire actif.'));

            return false;
        }
        if (!$this->security->isGranted('ROLE_ADMIN') && !$this->user->hasPartnerInTerritory($territory)) {
            $form->get($fieldAddress)->addError(new FormError('Vous n\'avez pas le droit de créer un signalement sur ce territoire.'));

            return false;
        }

        if (!$signalement->isTiersDeclarant()) {
            $signalement->setMailDeclarant(null);
            $signalement->setNomDeclarant(null);
            $signalement->setPrenomDeclarant(null);
            $signalement->setStructureDeclarant(null);
            $signalement->setTelDeclarant(null);
        }

        $signalement->setStatut(SignalementStatus::DRAFT);
        $signalement->setCreatedBy($this->user);
        $signalement->setTerritory($territory);
        $signalement->setIsCguAccepted(true);
        if (!$signalement->getReference()) {
            $signalement->setReference($this->referenceGenerator->generateReference($territory, false));
        }

        return true;
    }

    public function formLogementManager(FormInterface $form, Signalement $signalement): bool
    {
        $typeCompositionLogement = $signalement->getTypeCompositionLogement() ? clone $signalement->getTypeCompositionLogement() : new TypeCompositionLogement();
        $informationComplementaire = $signalement->getInformationComplementaire() ? clone $signalement->getInformationComplementaire() : new InformationComplementaire();

        if ('appartement' === $signalement->getNatureLogement()) {
            /** @var EtageType $appartementEtage */
            $appartementEtage = $form->get('appartementEtage')->getData();
            if (!empty($appartementEtage)) {
                $typeCompositionLogement->setTypeLogementAppartementEtage($appartementEtage->value);
                if (EtageType::RDC === $form->get('appartementEtage')->getData()) {
                    $typeCompositionLogement->setTypeLogementRdc('oui');
                } else {
                    $typeCompositionLogement->setTypeLogementRdc('non');
                }
            }

            $typeCompositionLogement->setTypeLogementAppartementAvecFenetres($form->get('appartementAvecFenetres')->getData());

            if (EtageType::DERNIER_ETAGE === $appartementEtage) {
                $typeCompositionLogement->setTypeLogementDernierEtage('oui');
                if ('non' === $typeCompositionLogement->getTypeLogementAppartementAvecFenetres()) {
                    $typeCompositionLogement->setTypeLogementSousCombleSansFenetre('oui');
                }
            } elseif (!empty($appartementEtage)) {
                $typeCompositionLogement->setTypeLogementDernierEtage('non');
            }

            if (EtageType::SOUSSOL === $appartementEtage
                    && 'non' === $typeCompositionLogement->getTypeLogementAppartementAvecFenetres()) {
                $typeCompositionLogement->setTypeLogementSousSolSansFenetre('oui');
            }
        }

        $informationComplementaire->setInformationsComplementairesLogementNombreEtages($form->get('nombreEtages')->getData());
        $informationComplementaire->setInformationsComplementairesLogementAnneeConstruction($form->get('anneeConstruction')->getData());

        $typeCompositionLogement->setCompositionLogementPieceUnique($form->get('pieceUnique')->getData());
        $typeCompositionLogement->setCompositionLogementNbPieces($form->get('nombrePieces')->getData());
        $signalement->setSuperficie($form->get('superficie')->getData());
        $typeCompositionLogement->setCompositionLogementSuperficie($form->get('superficie')->getData());
        $typeCompositionLogement->setTypeLogementCommoditesPieceAVivre9m($form->get('pieceAVivre9m')->getData());

        $cuisine = $form->get('cuisine')->getData();
        $sdb = $form->get('sdb')->getData();
        $toilettes = $form->get('toilettes')->getData();

        if ('oui' === $cuisine && 'oui' === $toilettes) {
            $typeCompositionLogement->setTypeLogementCommoditesWcCuisine($form->get('toilettesCuisineMemePiece')->getData());
        }
        if ('collective' === $cuisine) {
            $typeCompositionLogement->setTypeLogementCommoditesCuisineCollective('oui');
            $cuisine = 'non';
        }
        $typeCompositionLogement->setTypeLogementCommoditesCuisine($cuisine);
        if ('collective' === $sdb) {
            $typeCompositionLogement->setTypeLogementCommoditesSalleDeBainCollective('oui');
            $sdb = 'non';
        }
        $typeCompositionLogement->setTypeLogementCommoditesSalleDeBain($sdb);
        if ('collective' === $toilettes) {
            $typeCompositionLogement->setTypeLogementCommoditesWcCollective('oui');
            $toilettes = 'non';
        }
        $typeCompositionLogement->setTypeLogementCommoditesWc($toilettes);

        $jsonContent = $signalement->getJsonContent();
        $jsonContent['desordres_logement_chauffage'] = $form->get('typeChauffage')->getData();

        $signalement->setJsonContent($jsonContent);
        $signalement->setTypeCompositionLogement($typeCompositionLogement);
        $signalement->setInformationComplementaire($informationComplementaire);

        return true;
    }

    public function formSituationManager(FormInterface $form, Signalement $signalement): bool
    {
        $typeCompositionLogement = $signalement->getTypeCompositionLogement() ? clone $signalement->getTypeCompositionLogement() : new TypeCompositionLogement();
        $informationComplementaire = $signalement->getInformationComplementaire() ? clone $signalement->getInformationComplementaire() : new InformationComplementaire();
        $situationFoyer = $signalement->getSituationFoyer() ? clone $signalement->getSituationFoyer() : new SituationFoyer();
        $informationProcedure = $signalement->getInformationProcedure() ? clone $signalement->getInformationProcedure() : new InformationProcedure();

        $typeCompositionLogement->setBailDpeBail($form->get('bail')->getData());
        $typeCompositionLogement->setBailDpeDpe($form->get('dpe')->getData());
        $typeCompositionLogement->setBailDpeClasseEnergetique($form->get('classeEnergetique')->getData());
        $typeCompositionLogement->setDesordresLogementChauffageDetailsDpeAnnee($form->get('dateDpe')->getData());
        $typeCompositionLogement->setBailDpeEtatDesLieux($form->get('etatDesLieux')->getData());
        if ($form->get('dateEntreeLogement')->getData()) {
            $typeCompositionLogement->setBailDpeDateEmmenagement($form->get('dateEntreeLogement')->getData()->format('Y-m-d'));
            $signalement->setDateEntree($form->get('dateEntreeLogement')->getData());
        }
        $informationComplementaire->setInformationsComplementairesLogementMontantLoyer($form->get('montantLoyer')->getData());
        $informationComplementaire->setInformationsComplementairesSituationOccupantsLoyersPayes($form->get('payementLoyersAJour')->getData());
        if ('non' === $form->get('allocataire')->getData()) {
            $signalement->setIsAllocataire('0');
        } elseif (!empty($form->get('allocataire')->getData())) {
            if ('caf' === $form->get('caisseAllocation')->getData()) {
                $signalement->setIsAllocataire('caf');
            } elseif ('msa' === $form->get('caisseAllocation')->getData()) {
                $signalement->setIsAllocataire('msa');
            } else {
                $signalement->setIsAllocataire('1');
            }
        }
        if (!empty($form->get('dateNaissanceAllocataire')->getData())) {
            $signalement->setDateNaissanceOccupant(new \DateTimeImmutable($form->get('dateNaissanceAllocataire')->getData()->format('Y-m-d')));
        }
        $signalement->setNumAllocataire($form->get('numeroAllocataire')->getData());
        $informationComplementaire->setInformationsComplementairesSituationOccupantsTypeAllocation($form->get('typeAllocation')->getData());
        $signalement->setMontantAllocation((int) $form->get('montantAllocation')->getData());
        $situationFoyer->setTravailleurSocialAccompagnement($form->get('accompagnementTravailleurSocial')->getData());
        $situationFoyer->setTravailleurSocialAccompagnementNomStructure($form->get('accompagnementTravailleurSocialNomStructure')->getData());
        $informationComplementaire->setInformationsComplementairesSituationOccupantsBeneficiaireRsa($form->get('beneficiaireRSA')->getData());
        $informationComplementaire->setInformationsComplementairesSituationOccupantsBeneficiaireFsl($form->get('beneficiaireFSL')->getData());
        if (!empty($form->get('dateProprietaireAverti')->getData())) {
            $signalement->setProprioAvertiAt(new \DateTimeImmutable($form->get('dateProprietaireAverti')->getData()->format('Y-m-d')));
        }
        $informationProcedure->setInfoProcedureBailMoyen($form->get('moyenInformationProprietaire')->getData());
        $informationProcedure->setInfoProcedureBailReponse($form->get('reponseProprietaire')->getData());
        $situationFoyer->setTravailleurSocialQuitteLogement($form->get('souhaiteQuitterLogement')->getData());
        $situationFoyer->setTravailleurSocialPreavisDepart($form->get('preavisDepartDepose')->getData());
        if ('oui' === $form->get('logementAssure')->getData()) {
            if ('oui' === $form->get('assuranceContactee')->getData()) {
                $informationProcedure->setInfoProcedureAssuranceContactee('oui');
            } elseif ('non' === $form->get('assuranceContactee')->getData()) {
                $informationProcedure->setInfoProcedureAssuranceContactee('non');
            }
        } elseif ('non' === $form->get('logementAssure')->getData()) {
            $informationProcedure->setInfoProcedureAssuranceContactee('pas_assurance_logement');
        }
        $informationProcedure->setInfoProcedureReponseAssurance($form->get('reponseAssurance')->getData());

        $signalement->setTypeCompositionLogement($typeCompositionLogement);
        $signalement->setInformationComplementaire($informationComplementaire);
        $signalement->setSituationFoyer($situationFoyer);
        $signalement->setInformationProcedure($informationProcedure);

        return true;
    }

    public function formDesordresManager(FormInterface $form, Signalement $signalement): bool
    {
        $signalement->setDetails($form->get('details')->getData());

        $signalement->removeAllDesordrePrecision();
        $jsonContent = $signalement->getJsonContent();
        if (array_key_exists('desordres_batiment_nuisibles_autres', $jsonContent)) {
            unset($jsonContent['desordres_batiment_nuisibles_autres']);
        }
        if (array_key_exists('desordres_logement_nuisibles_autres', $jsonContent)) {
            unset($jsonContent['desordres_logement_nuisibles_autres']);
        }

        foreach ($form->all() as $field) {
            $fieldName = $field->getName();
            $fieldData = $field->getData();
            if (empty($fieldData) || ($fieldData instanceof ArrayCollection && $fieldData->isEmpty())) {
                continue;
            }

            if (str_starts_with($fieldName, 'desordres_LOGEMENT') || str_starts_with($fieldName, 'desordres_BATIMENT')) {
                /** @var DesordreCritere $desordreCritere */
                foreach ($fieldData as $desordreCritere) {
                    if ($desordreCritere->getDesordrePrecisions()->count() < 2) {
                        $first = $desordreCritere->getDesordrePrecisions()->first();
                        if ($first instanceof DesordrePrecision) {
                            $signalement->addDesordrePrecision($first);
                        }
                    }
                }
            }
            if (str_starts_with($fieldName, 'precisions_')) {
                if (str_ends_with($fieldName, 'details_type_nuisibles') || str_contains($fieldName, '_plafond_trop_bas')) {
                    $idJsonData = $this->extractCritere($fieldName);
                    $jsonContent[$idJsonData] = $fieldData;
                } else {
                    /** @var DesordrePrecision $desordrePrecision */
                    foreach ($fieldData as $desordrePrecision) {
                        $signalement->addDesordrePrecision($desordrePrecision);
                    }
                }
            }
        }

        $signalement->setJsonContent($jsonContent);

        return true;
    }

    private function extractCritere(string $fieldName): ?string
    {
        // Expression régulière pour capturer la partie "desordres_*_nuisibles_autres"
        if (preg_match('/precisions_\d+_(desordres_[a-z_]+_nuisibles_autres)_details_type_nuisibles/', $fieldName, $matches)) {
            return $matches[1];
        }

        // Expression régulière pour capturer la partie "desordres_*_nuisibles_autres" "desordres_*_lumiere_plafond_trop_bas_piece_a_vivre"
        if (preg_match('/precisions_\d+_(desordres_logement_lumiere_plafond_trop_bas[a-z_]+)/', $fieldName, $matches)) {
            return $matches[1];
        }

        return null;
    }
}
