<?php

namespace App\Service\Signalement;

use App\Dto\Api\Request\SignalementRequest;
use App\Entity\Enum\EtageType;
use App\Entity\Enum\ProfileDeclarant;
use App\Entity\Enum\ProprioType;
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
        $typeCompositionLogement = new TypeCompositionLogement();
        $situationFoyer = new SituationFoyer();
        $informationProcedure = new InformationProcedure();
        $informationComplementaire = new InformationComplementaire();

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

        $typeCompositionLogement->setBailDpeBail(self::convertBoolToString($request->isBail));
        $typeCompositionLogement->setBailDpeDpe(self::convertBoolToString($request->isDpe));
        if ($request->anneeDpe) {
            if ($request->anneeDpe < 2023) {
                $typeCompositionLogement->setDesordresLogementChauffageDetailsDpeAnnee('before2023');
            } elseif ($request->anneeDpe > (int) date('Y')) {
                $typeCompositionLogement->setDesordresLogementChauffageDetailsDpeAnnee('post2023');
            }
        }
        $typeCompositionLogement->setBailDpeClasseEnergetique($request->classeEnergetique);
        $typeCompositionLogement->setBailDpeEtatDesLieux(self::convertBoolToString($request->isEtatDesLieux));
        if ($request->dateEntreeLogement) {
            $dateEntree = new \DateTimeImmutable($request->dateEntreeLogement);
            $typeCompositionLogement->setBailDpeDateEmmenagement($dateEntree->format('Y-m-d'));
            $signalement->setDateEntree($dateEntree);
        }
        $informationComplementaire->setInformationsComplementairesLogementMontantLoyer((string) $request->montantLoyer);
        $informationComplementaire->setInformationsComplementairesSituationOccupantsLoyersPayes(self::convertBoolToString($request->isPaiementLoyersAJour));

        if ($request->isAllocataire) {
            if (in_array($request->caisseAllocation, ['CAF', 'MSA'])) {
                $signalement->setIsAllocataire(mb_strtolower($request->caisseAllocation));
            } else {
                $signalement->setIsAllocataire('1');
            }
            if ($request->dateNaissanceAllocataire) {
                $signalement->setDateNaissanceOccupant(new \DateTimeImmutable($request->dateNaissanceAllocataire));
            }
            $signalement->setNumAllocataire($request->numAllocataire);
            $informationComplementaire->setInformationsComplementairesSituationOccupantsTypeAllocation(mb_strtolower($request->typeAllocation));
            $signalement->setMontantAllocation($request->montantAllocation);
        } elseif (false === $request->isAllocataire) {
            $signalement->setIsAllocataire('0');
        }

        $situationFoyer->setTravailleurSocialAccompagnement(self::convertBoolToString($request->isAccompagnementTravailleurSocial));
        $situationFoyer->setTravailleurSocialAccompagnementNomStructure($request->accompagnementTravailleurSocialNomStructure);
        $informationComplementaire->setInformationsComplementairesSituationOccupantsBeneficiaireRsa(self::convertBoolToString($request->isBeneficiaireRsa));
        $informationComplementaire->setInformationsComplementairesSituationOccupantsBeneficiaireFsl(self::convertBoolToString($request->isBeneficiaireFsl));

        $signalement->setIsProprioAverti($request->isBailleurAverti);
        if ($request->isBailleurAverti) {
            if ($request->dateBailleurAverti) {
                $signalement->setProprioAvertiAt(new \DateTimeImmutable($request->dateBailleurAverti));
            }
            $informationProcedure->setInfoProcedureBailMoyen($request->moyenInformationBailleur);
            $informationProcedure->setInfoProcedureBailReponse($request->reponseBailleur);
        }

        $signalement->setIsRelogement($request->isDemandeRelogement);
        $situationFoyer->setTravailleurSocialQuitteLogement(self::convertBoolToString($request->isSouhaiteQuitterLogement));
        $situationFoyer->setTravailleurSocialPreavisDepart(self::convertBoolToString($request->isPreavisDepartDepose));
        $signalement->setIsPreavisDepart($request->isPreavisDepartDepose);

        if ($request->isLogementAssure) {
            if ($request->isAssuranceContactee) {
                $informationProcedure->setInfoProcedureAssuranceContactee('oui');
                $informationProcedure->setInfoProcedureReponseAssurance($request->reponseAssurance);
            } elseif (false === $request->isAssuranceContactee) {
                $informationProcedure->setInfoProcedureAssuranceContactee('non');
            }
        } elseif (false === $request->isLogementAssure) {
            $informationProcedure->setInfoProcedureAssuranceContactee('pas_assurance_logement');
        }
        if ($request->civiliteOccupant) {
            $signalement->setCiviliteOccupant(mb_strtolower($request->civiliteOccupant));
        }
        $signalement->setNomOccupant($request->nomOccupant);
        $signalement->setPrenomOccupant($request->prenomOccupant);
        $signalement->setMailOccupant($request->mailOccupant);
        $signalement->setTelOccupant($request->telOccupant);
        if ($request->typeBailleur) {
            $signalement->setTypeProprio(ProprioType::from($request->typeBailleur));
        }
        if ('ORGANISME_SOCIETE' === $request->typeBailleur) {
            $signalement->setDenominationProprio($request->denominationBailleur);
        }
        $signalement->setNomProprio($request->nomBailleur);
        $signalement->setPrenomProprio($request->prenomBailleur);
        $signalement->setMailProprio($request->mailBailleur);
        $signalement->setTelProprio($request->telBailleur);
        $signalement->setAdresseProprio($request->adresseBailleur);
        $signalement->setCodePostalProprio($request->codePostalBailleur);
        $signalement->setVilleProprio($request->communeBailleur);

        if ($signalement->isTiersDeclarant()) {
            $signalement->setIsNotOccupant(true);
            $signalement->setStructureDeclarant($request->structureDeclarant);
            $signalement->setNomDeclarant($request->nomDeclarant);
            $signalement->setPrenomDeclarant($request->prenomDeclarant);
            $signalement->setMailDeclarant($request->mailDeclarant);
            $signalement->setTelDeclarant($request->telDeclarant);
        } else {
            $signalement->setIsNotOccupant(false);
        }
        $signalement->setDenominationAgence($request->denominationAgence);
        $signalement->setNomAgence($request->nomAgence);
        $signalement->setPrenomAgence($request->prenomAgence);
        $signalement->setMailAgence($request->mailAgence);
        $signalement->setTelAgence($request->telAgence);

        // TODO : tab désordres
        // TODO : tab validation ?

        $signalement->setTypeCompositionLogement($typeCompositionLogement);
        $signalement->setSituationFoyer($situationFoyer);
        $signalement->setInformationProcedure($informationProcedure);
        $signalement->setInformationComplementaire($informationComplementaire);

        // TODO : adapter l'affichage BO pour les création API
        $signalement->setCreatedBy($this->user);
        // TODO : voir avec équipe (champ obligatoire)
        $signalement->setIsCguAccepted(false);

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
