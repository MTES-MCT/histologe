<?php

namespace App\Service\Signalement;

use App\Dto\Api\Request\SignalementRequest;
use App\Entity\Enum\ProfileDeclarant;
use App\Entity\Model\InformationComplementaire;
use App\Entity\Model\InformationProcedure;
use App\Entity\Model\SituationFoyer;
use App\Entity\Model\TypeCompositionLogement;
use App\Entity\Signalement;
use App\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;

class SignalementApiFactory
{
    private User $user;

    public function __construct(
        private readonly Security $security,
        private readonly SignalementAddressUpdater $signalementAddressUpdater,
        private readonly PostalCodeHomeChecker $postalCodeHomeChecker,
    ) {
        /** @var User $user */
        $user = $this->security->getUser();
        $this->user = $user;
    }

    public function createFromSignalementRequest(SignalementRequest $request): Signalement
    {
        $signalement = new Signalement();

        $signalement->setAdresseOccupant($request->adresseOccupant);
        $signalement->setCpOccupant($request->codePostalOccupant);
        $signalement->setVilleOccupant($request->communeOccupant);
        $this->signalementAddressUpdater->updateAddressOccupantFromBanData($signalement);
        $territory = $this->postalCodeHomeChecker->getActiveTerritory($signalement->getInseeOccupant());
        $signalement->setTerritory($territory);

        $signalement->setEtageOccupant($request->etageOccupant);
        $signalement->setEscalierOccupant($request->escalierOccupant);
        $signalement->setNumAppartOccupant(numAppartOccupant: $request->numAppartOccupant);
        $signalement->setAdresseAutreOccupant($request->adresseAutreOccupant);

        $signalement->setProfileDeclarant(ProfileDeclarant::from($request->profilDeclarant));
        if (ProfileDeclarant::TIERS_PARTICULIER === $signalement->getProfileDeclarant() && $request->lienDeclarantOccupant) {
            $signalement->setLienDeclarantOccupant($request->lienDeclarantOccupant);
        }

        $signalement->setIsLogementSocial($request->isLogementSocial);
        if (in_array($signalement->getProfileDeclarant(), [ProfileDeclarant::LOCATAIRE, ProfileDeclarant::BAILLEUR_OCCUPANT])) {
            $signalement->setIsLogementVacant($request->isLogementVacant);
        } else {
            $signalement->setIsLogementVacant(false);
        }

        $typeCompositionLogement = new TypeCompositionLogement();
        $typeCompositionLogement->setCompositionLogementNombrePersonnes((string) $request->nbOccupantsLogement);
        $signalement->setNbOccupantsLogement($request->nbOccupantsLogement);
        $typeCompositionLogement->setCompositionLogementNombreEnfants((string) $request->nbEnfantsDansLogement);
        $typeCompositionLogement->setCompositionLogementEnfants((string) $request->isEnfantsMoinsSixAnsDansLogement);
        $signalement->setTypeCompositionLogement($typeCompositionLogement);

        $situationFoyer = new SituationFoyer();
        $signalement->setSituationFoyer($situationFoyer);

        $informationProcedure = new InformationProcedure();
        $signalement->setInformationProcedure($informationProcedure);

        $informationComplementaire = new InformationComplementaire();
        $signalement->setInformationComplementaire($informationComplementaire);

        // TODO : continuer

        // TODO : adapter l'affichage BO pour les création API
        $signalement->setCreatedBy($this->user);

        return $signalement;
    }
}
