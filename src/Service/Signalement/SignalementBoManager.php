<?php

namespace App\Service\Signalement;

use App\Entity\Enum\ProfileDeclarant;
use App\Entity\Enum\SignalementStatus;
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
        switch ($form->get('occupationLogement')->getData()) {
            case 'bail_en_cours':
                $signalement->setIsBailEnCours(true);
                $signalement->setIsLogementVacant(false);
                if (ProfileDeclarant::BAILLEUR_OCCUPANT === $signalement->getProfileDeclarant()) {
                    $signalement->setProfileDeclarant(null);
                }
                break;
            case 'proprio_occupant':
                $signalement->setProfileDeclarant(ProfileDeclarant::BAILLEUR_OCCUPANT);
                $signalement->setIsBailEnCours(false);
                $signalement->setIsLogementVacant(false);
                break;
            case 'logement_vacant':
                $signalement->setIsLogementVacant(true);
                $signalement->setIsBailEnCours(false);
                if (ProfileDeclarant::BAILLEUR_OCCUPANT === $signalement->getProfileDeclarant()) {
                    $signalement->setProfileDeclarant(null);
                }
                break;
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
}
