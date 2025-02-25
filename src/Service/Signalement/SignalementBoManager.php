<?php

namespace App\Service\Signalement;

use App\Entity\Enum\ProfileDeclarant;
use App\Entity\Enum\SignalementStatus;
use App\Entity\Model\InformationComplementaire;
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

        $territory = $this->postalCodeHomeChecker->getActiveTerritory($signalement->getCpOccupant(), $signalement->getInseeOccupant());
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
            $typeCompositionLogement->setTypeLogementAppartementEtage($form->get('appartementEtage')->getData());
            $typeCompositionLogement->setTypeLogementAppartementAvecFenetres($form->get('appartementAvecFenetres')->getData());
            if ('rdc' === $typeCompositionLogement->getTypeLogementAppartementEtage()) {
                $typeCompositionLogement->setTypeLogementRdc('oui');
            } elseif ('' !== $typeCompositionLogement->getTypeLogementAppartementEtage()) {
                $typeCompositionLogement->setTypeLogementRdc('non');
            }

            if ('dernier_etage' === $typeCompositionLogement->getTypeLogementAppartementEtage()) {
                $typeCompositionLogement->setTypeLogementDernierEtage('oui');
                if ('non' === $typeCompositionLogement->getTypeLogementAppartementAvecFenetres()) {
                    $typeCompositionLogement->setTypeLogementSousCombleSansFenetre('oui');
                }
            } elseif ('' !== $typeCompositionLogement->getTypeLogementAppartementEtage()) {
                $typeCompositionLogement->setTypeLogementDernierEtage('non');
            }

            if ('sous-sol' === $typeCompositionLogement->getTypeLogementAppartementEtage()
                    && 'non' === $typeCompositionLogement->getTypeLogementAppartementAvecFenetres()) {
                $typeCompositionLogement->setTypeLogementSousSolSansFenetre('oui');
            }
        }

        $informationComplementaire->setInformationsComplementairesLogementNombreEtages($form->get('nombreEtages')->getData());
        $informationComplementaire->setInformationsComplementairesLogementAnneeConstruction($form->get('anneeConstruction')->getData());

        $typeCompositionLogement->setCompositionLogementPieceUnique($form->get('pieceUnique')->getData());
        $typeCompositionLogement->setCompositionLogementNbPieces($form->get('nombrePieces')->getData());
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
}
