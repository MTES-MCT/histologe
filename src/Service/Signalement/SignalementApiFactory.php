<?php

namespace App\Service\Signalement;

use App\Dto\Api\Request\SignalementRequest;
use App\Entity\Enum\EtageType;
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

        $signalement->setNatureLogement($request->natureLogement);
        if ('autre' === $signalement->getNatureLogement()) {
            $typeCompositionLogement->setTypeLogementNatureAutrePrecision($request->natureLogementAutre);
        }
        if ('appartement' === $signalement->getNatureLogement()) {
            $apptAvecFenetre = self::convertBoolToString($request->isAppartementAvecFenetres);
            $typeCompositionLogement->setTypeLogementAppartementAvecFenetres($apptAvecFenetre);

            if ($request->etageAppartement) {
                $typeCompositionLogement->setTypeLogementDernierEtage('non');
                $typeCompositionLogement->setTypeLogementRdc('non');
                $typeCompositionLogement->setTypeLogementSousCombleSansFenetre('non');
                $typeCompositionLogement->setTypeLogementSousSolSansFenetre('non');

                if (EtageType::RDC->value === $request->etageAppartement) {
                    $typeCompositionLogement->setTypeLogementRdc('oui');
                } elseif (EtageType::DERNIER_ETAGE->value === $request->etageAppartement) {
                    $typeCompositionLogement->setTypeLogementDernierEtage('oui');
                    if ('non' === $apptAvecFenetre) {
                        $typeCompositionLogement->setTypeLogementSousCombleSansFenetre('oui');
                    }
                } elseif (EtageType::SOUSSOL->value === $request->etageAppartement && 'non' === $apptAvecFenetre) {
                    $typeCompositionLogement->setTypeLogementSousSolSansFenetre('oui');
                }
            }
        }
        if (1 === $request->nombrePieces) {
            $typeCompositionLogement->setCompositionLogementPieceUnique('piece_unique');
        } elseif ($request->nombrePieces > 1) {
            $typeCompositionLogement->setCompositionLogementPieceUnique('plusieurs_pieces');
        }
        $typeCompositionLogement->setCompositionLogementNbPieces((string) $request->nombrePieces);
        $signalement->setSuperficie($request->superficie);
        $typeCompositionLogement->setCompositionLogementSuperficie((string) $request->superficie);
        $typeCompositionLogement->setTypeLogementCommoditesPieceAVivre9m(self::convertBoolToString($request->isPieceAVivre9m));
        $typeCompositionLogement->setTypeLogementCommoditesCuisine(self::convertBoolToString($request->isCuisine));
        $typeCompositionLogement->setTypeLogementCommoditesSalleDeBain(self::convertBoolToString($request->isSdb));
        $typeCompositionLogement->setTypeLogementCommoditesWc(self::convertBoolToString($request->isWc));
        if (false === $request->isCuisine && null !== $request->isCuisineCollective) {
            $typeCompositionLogement->setTypeLogementCommoditesCuisineCollective(self::convertBoolToString($request->isCuisineCollective));
        }
        if (false === $request->isSdb && null !== $request->isSdbCollective) {
            $typeCompositionLogement->setTypeLogementCommoditesSalleDeBainCollective(self::convertBoolToString($request->isSdbCollective));
        }
        if ($request->isCuisine && $request->isWc) {
            $typeCompositionLogement->setTypeLogementCommoditesWcCuisine(self::convertBoolToString($request->isWcCuisineMemePiece));
        }
        $jsonContent['desordres_logement_chauffage'] = $request->typeChauffage;
        $signalement->setJsonContent($jsonContent);

        // TODO :  TAB situation
        //

        $situationFoyer = new SituationFoyer();
        $informationProcedure = new InformationProcedure();
        $informationComplementaire = new InformationComplementaire();

        $signalement->setTypeCompositionLogement($typeCompositionLogement);
        $signalement->setSituationFoyer($situationFoyer);
        $signalement->setInformationProcedure($informationProcedure);
        $signalement->setInformationComplementaire($informationComplementaire);

        // TODO : adapter l'affichage BO pour les création API
        $signalement->setCreatedBy($this->user);

        return $signalement;
    }

    private static function convertBoolToString(?bool $value): ?string
    {
        if (true === $value) {
            return 'oui';
        }
        if (false === $value) {
            return 'non';
        }

        return null;
    }
}
