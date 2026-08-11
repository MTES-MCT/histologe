<?php

namespace App\Factory;

use App\Entity\Enum\CreationSource;
use App\Entity\Enum\MotifCloture;
use App\Entity\Enum\SignalementStatus;
use App\Entity\Signalement;
use App\Entity\Territory;
use App\Exception\Address\TerritoryInconsistentException;
use App\Service\Signalement\SignalementAddressUpdater;

class SignalementImportFactory
{
    public function __construct(
        private readonly SignalementAddressUpdater $signalementAddressUpdater,
    ) {
    }

    /**
     * @param array<int|string, mixed> $data
     */
    public function create(Territory $territory, array $data): Signalement
    {
        if (empty($data['statut'])) {
            $data['statut'] = SignalementStatus::ACTIVE;
            if ($data['motifCloture'] || $data['closedAt']) {
                $data['statut'] = SignalementStatus::CLOSED;
            }
        }

        $signalement = (new Signalement())
            ->setIsImported(true)
            ->setCreationSource(CreationSource::IMPORT)
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
            ->setIsCguAccepted((bool) $data['isCguAccepted'])
            ->setCreatedAt($data['createdAt'])
            ->setModifiedAt($data['modifiedAt'])
            ->setStatut($data['statut'])
            ->setValidatedAt(
                SignalementStatus::ACTIVE === $data['statut'] ? $data['createdAt'] : new \DateTimeImmutable()
            )
            ->setReference($data['reference'])
            ->setMontantAllocation((float) $data['montantAllocation'])
            ->setEtageOccupant($data['etageOccupant'])
            ->setEscalierOccupant($data['escalierOccupant'])
            ->setNumAppartOccupant($data['numAppartOccupant'])
            ->setAdresseAutreOccupant($data['adresseAutreOccupant'])
            ->setLienDeclarantOccupant($data['lienDeclarantOccupant'])
            ->setIsRsa((bool) $data['isRsa'])
            ->setAnneeConstruction($data['anneeConstruction'])
            ->setNaissanceOccupants($data['naissanceOccupants'])
            ->setIsConstructionAvant1949((bool) $data['isConstructionAvant1949'])
            ->setProprioAvertiAt($data['prorioAvertiAt'])
            ->setNumeroInvariant($data['numeroInvariant'])
            ->setNbPiecesLogement((int) $data['nbPiecesLogement'])
            ->setNbNiveauxLogement((int) $data['nbNiveauxLogement'])
            ->setNbOccupantsLogement((int) $data['nbOccupantsLogement'])
            ->setMotifCloture(
                null !== $data['motifCloture']
                    ? MotifCloture::tryFrom($data['motifCloture'])
                    : null)
            ->setClosedAt($data['closedAt']);

        $this->signalementAddressUpdater->attachAddressToSignalement($signalement, $data['adresseOccupant'], $data['cpOccupant'], $data['villeOccupant']);

        if ($signalement->getAddress()->getTerritory()->getId() !== $territory->getId()) {
            throw new TerritoryInconsistentException($signalement->getAddress()->getTerritory(), $territory);
        }

        $this->signalementAddressUpdater->getRnbDataForSignalement($signalement);
        $this->signalementAddressUpdater->getRialDataForSignalement($signalement);

        return $signalement;
    }
}
