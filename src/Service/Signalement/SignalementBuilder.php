<?php

namespace App\Service\Signalement;

use App\Dto\Request\Signalement\SignalementDraftRequest;
use App\Entity\Signalement;
use App\Entity\SignalementDraft;
use App\Repository\TerritoryRepository;

class SignalementBuilder
{
    private Signalement $signalement;
    private SignalementDraftRequest $signalementDraftRequest;

    public function __construct(
        private SignalementDraft $signalementDraft,
        private TerritoryRepository $territoryRepository,
        private ZipcodeProvider $zipcodeProvider,
    ) {
        $this->signalementDraftRequest = $this->signalementDraft->getSignalementDraftRequest();
    }

    public function createSignalementBuilder(): self
    {
        $territory = $this->territoryRepository->findOneBy([
            'zip' => $this
                ->zipcodeProvider
                ->getZipCode($this->signalementDraftRequest->getAdresseLogementAdresseDetailCodePostal()),
        ]);

        $this->signalement = (new Signalement())->setTerritory($territory);

        return $this;
    }

    public function withAddressesCoordonees(): self
    {
        $this->signalement
            ->setAdresseOccupant($this->signalementDraftRequest->getAdresseLogementAdresseDetailNumero())
            ->setCpOccupant($this->signalementDraftRequest->getAdresseLogementAdresseDetailCodePostal())
            ->setInseeOccupant($this->signalementDraftRequest->getAdresseLogementAdresseDetailInsee())
            ->setGeoloc([
                $this->signalementDraftRequest->getAdresseLogementAdresseDetailGeolocLat(),
                $this->signalementDraftRequest->getAdresseLogementAdresseDetailGeolocLng(),
            ])
            ->setVilleOccupant($this->signalementDraftRequest->getAdresseLogementAdresseDetailCommune())
            ->setEtageOccupant($this->signalementDraftRequest->getAdresseLogementComplementAdresseEtage())
            ->setEscalierOccupant($this->signalementDraftRequest->getAdresseLogementComplementAdresseEscalier())
            ->setNumAppartOccupant($this->signalementDraftRequest->getAdresseLogementComplementAdresseNumeroAppartement())
            ->setAdresseAutreOccupant($this->signalementDraftRequest->getAdresseLogementComplementAdresseAutre());

        $this->signalement
            ->setCiviiliteOccupant($this->signalementDraftRequest->getVosCoordonneesOccupantCivilite())
            ->setMailOccupant($this->signalementDraftRequest->getVosCoordonneesOccupantEmail())
            ->setNomOccupant($this->signalementDraftRequest->getVosCoordonneesOccupantNom())
            ->setPrenomOccupant($this->signalementDraftRequest->getVosCoordonneesOccupantPrenom())
            ->setTelOccupant($this->signalementDraftRequest->getVosCoordonneesOccupantTel())
            ->setTelOccupantBis($this->signalementDraftRequest->getVosCoordonneesOccupantTelSecondaire());

        $this->signalement
            ->setAdresseProprio($this->signalementDraftRequest->getCoordonneesBailleurAdresse())
            ->setMailProprio($this->signalementDraftRequest->getCoordonneesBailleurEmail())
            ->setNomProprio($this->signalementDraftRequest->getCoordonneesBailleurNom())
            ->setPrenomProprio($this->signalementDraftRequest->getCoordonneesBailleurPrenom())
            ->setTelProprio($this->signalementDraftRequest->getCoordonneesBailleurTel());

        return $this;
    }

    public function withTypeCompositionLogement(): self
    {
        $this->signalement
            ->setNatureLogement($this->signalementDraftRequest->getTypeLogementNature())
            ->setSuperficie($this->signalementDraftRequest->getCompositionLogementSuperficie())
            ->setNbPiecesLogement($this->signalementDraftRequest->getCompositionLogementNbPieces())
            ->setTypeComposition([]);

        return $this;
    }

    public function withSituationFoyer(): self
    {
        $this->signalement
            ->setIsBailEnCours($this->signalementDraftRequest->getBailDpeDpe())
            ->setDateEntree($this->signalementDraftRequest->getBailDpeDateEmmenagement())
            ->setIsRelogement($this->signalementDraftRequest->getLogementSocialDemandeRelogement())
            ->setIsAllocataire($this->signalementDraftRequest->getLogementSocialAllocationCaisse())
            ->setNumAllocataire($this->signalementDraftRequest->getLogementSocialMontantAllocation())
            ->setMontantAllocation($this->signalementDraftRequest->getLogementSocialMontantAllocation())
            ->setDateNaissanceOccupant($this->signalementDraftRequest->getLogementSocialDateNaissance())
            ->setSituationFoyer([]);

        return $this;
    }

    public function withDesordres(): self
    {
        // TODO : https://github.com/MTES-MCT/histologe/issues/1665
        return $this;
    }

    public function withProcedure(): self
    {
        $this->signalement
            ->setIsProprioAverti($this->signalementDraftRequest->getInfoProcedureBailleurPrevenu())
            ->setIsRsa($this->signalementDraftRequest->getInformationsComplementairesSituationOccupantsBeneficiaireRsa())
            ->setIsFondSolidariteLogement($this->signalementDraftRequest->getInformationsComplementairesSituationOccupantsBeneficiaireFsl())
            ->setLoyer($this->signalementDraftRequest->getInformationsComplementairesLogementMontantLoyer())
            ->setNbNiveauxLogement($this->signalementDraftRequest->getInformationsComplementairesLogementNombreEtages())
            ->setInformationProcedure([]);

        return $this;
    }

    public function build(): Signalement
    {
        return $this->signalement;
    }
}
