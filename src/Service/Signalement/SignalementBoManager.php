<?php

namespace App\Service\Signalement;

use App\Entity\Enum\EtageType;
use App\Entity\Enum\ProfileDeclarant;
use App\Entity\Enum\SignalementStatus;
use App\Entity\Model\InformationComplementaire;
use App\Entity\Model\InformationProcedure;
use App\Entity\Model\SituationFoyer;
use App\Entity\Model\TypeCompositionLogement;
use App\Entity\Signalement;
use App\Entity\User;
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
        $signalement->setIsBailEnCours('bail_en_cours' === $form->get('occupationLogement')->getData());
        $signalement->setIsLogementVacant('logement_vacant' === $form->get('occupationLogement')->getData());
        if ('proprio_occupant' === $form->get('occupationLogement')->getData()) {
            $signalement->setProfileDeclarant(ProfileDeclarant::BAILLEUR_OCCUPANT);
        } elseif (ProfileDeclarant::BAILLEUR_OCCUPANT === $signalement->getProfileDeclarant()) {
            $signalement->setProfileDeclarant(null);
        }
        $typeCompositionLogement = $signalement->getTypeCompositionLogement() ? clone $signalement->getTypeCompositionLogement() : new TypeCompositionLogement();
        $typeCompositionLogement->setCompositionLogementNombreEnfants($form->get('nbEnfantsDansLogement')->getData());
        $typeCompositionLogement->setCompositionLogementEnfants($form->get('enfantsDansLogementMoinsSixAns')->getData());
        $signalement->setTypeCompositionLogement($typeCompositionLogement);

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

        $signalement->setStatut(SignalementStatus::DRAFT);
        $signalement->setCreatedBy($this->user);
        $signalement->setTerritory($territory);
        $signalement->setIsCguAccepted(true);
        $signalement->setReference($this->referenceGenerator->generate($territory));

        return true;
    }

    public function formLogementManager(FormInterface $form, Signalement $signalement): bool
    {
        $typeCompositionLogement = $signalement->getTypeCompositionLogement() ? clone $signalement->getTypeCompositionLogement() : new TypeCompositionLogement();
        $informationComplementaire = $signalement->getInformationComplementaire() ? clone $signalement->getInformationComplementaire() : new InformationComplementaire();

        $signalement->setNatureLogement($form->get('natureLogement')->getData());
        if ('autre' === $signalement->getNatureLogement()) {
            $typeCompositionLogement->setTypeLogementNatureAutrePrecision($form->get('natureLogementAutre')->getData());
        }
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
        $typeCompositionLogement->setCompositionLogementHauteur($form->get('hauteur')->getData());
        $typeCompositionLogement->setTypeLogementCommoditesPieceAVivre9m($form->get('pieceAVivre9m')->getData());

        $cuisine = $form->get('cuisine')->getData();
        $sdb = $form->get('sdb')->getData();
        $toilettes = $form->get('toilettes')->getData();

        if ('oui' === $cuisine && 'oui' === $toilettes) {
            $typeCompositionLogement->setTypeLogementCommoditesWcCuisine($form->get('toilettesCuisineMemePiece')->getData());
        }
        if ('collective' === $cuisine) {
            $typeCompositionLogement->setTypeLogementCommoditesCuisineCollective('oui');
            $cuisine = 'oui';
        }
        $typeCompositionLogement->setTypeLogementCommoditesCuisine($cuisine);
        if ('collective' === $sdb) {
            $typeCompositionLogement->setTypeLogementCommoditesSalleDeBainCollective('oui');
            $sdb = 'oui';
        }
        $typeCompositionLogement->setTypeLogementCommoditesSalleDeBain($sdb);
        if ('collective' === $toilettes) {
            $typeCompositionLogement->setTypeLogementCommoditesWcCollective('oui');
            $toilettes = 'oui';
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
        $typeCompositionLogement->setBailDpeEtatDesLieux($form->get('etatDesLieux')->getData());
        $typeCompositionLogement->setBailDpeDateEmmenagement($form->get('dateEntreeLogement')->getData());
        $informationComplementaire->setInformationsComplementairesLogementMontantLoyer($form->get('montantLoyer')->getData());
        $informationComplementaire->setInformationsComplementairesSituationOccupantsLoyersPayes($form->get('payementLoyersAJour')->getData());
        if ('non' === $form->get('allocataire')->getData()) {
            $signalement->setIsAllocataire('0');
        } elseif (!empty($form->get('allocataire')->getData())) {
            if ('caf' === $form->get('allocataire')->getData()) {
                $signalement->setIsAllocataire('caf');
            } elseif ('msa' === $form->get('allocataire')->getData()) {
                $signalement->setIsAllocataire('msa');
            } else {
                $signalement->setIsAllocataire('1');
            }
        }
        $signalement->setDateNaissanceOccupant($form->get('dateNaissanceAllocataire')->getData());
        $signalement->setNumAllocataire($form->get('numeroAllocataire')->getData());
        $informationComplementaire->setInformationsComplementairesSituationOccupantsTypeAllocation($form->get('typeAllocation')->getData());
        $signalement->setMontantAllocation($form->get('montantAllocation')->getData());
        $situationFoyer->setTravailleurSocialAccompagnement($form->get('accompagnementTravailleurSocial')->getData());
        $informationComplementaire->setInformationsComplementairesSituationOccupantsBeneficiaireRsa($form->get('beneficiaireRSA')->getData());
        $informationComplementaire->setInformationsComplementairesSituationOccupantsBeneficiaireFsl($form->get('beneficiaireFSL')->getData());
        if ('non' === $form->get('proprietaireAverti')->getData()) {
            $signalement->setIsProprioAverti(false);
        } elseif ('oui' === $form->get('proprietaireAverti')->getData()) {
            $signalement->setIsProprioAverti(true);
        }
        $signalement->setProprioAvertiAt(new \DateTimeImmutable($form->get('dateProprietaireAverti')->getData()));
        $informationProcedure->setInfoProcedureBailMoyen($form->get('moyenInformationProprietaire')->getData());
        $informationProcedure->setInfoProcedureBailReponse($form->get('reponseProprietaire')->getData());
        if ('non' === $form->get('demandeRelogement')->getData()) {
            $signalement->setIsRelogement(false);
        } elseif ('oui' === $form->get('demandeRelogement')->getData()) {
            $signalement->setIsRelogement(true);
        }
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
}
