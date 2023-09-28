<?php

namespace App\Service\Signalement;

use App\Dto\Request\Signalement\SignalementDraftRequest;
use App\Entity\Enum\ProfileDeclarant;
use App\Entity\Signalement;
use App\Entity\SignalementDraft;
use App\Repository\TerritoryRepository;
use App\Serializer\SignalementDraftRequestSerializer;
use App\Utils\DataPropertyArrayFilter;

class SignalementBuilder
{
    private Signalement $signalement;
    private SignalementDraft $signalementDraft;
    private SignalementDraftRequest $signalementDraftRequest;

    public function __construct(
        private TerritoryRepository $territoryRepository,
        private ZipcodeProvider $zipcodeProvider,
        private ReferenceGenerator $referenceGenerator,
        private SignalementDraftRequestSerializer $serializer,
    ) {
    }

    public function createSignalementBuilderFrom(SignalementDraft $signalementDraft): self
    {
        $this->signalementDraft = $signalementDraft;

        $this->signalementDraftRequest = $this->serializer->denormalize(
            $signalementDraft->getPayload(),
            SignalementDraftRequest::class
        );

        $territory = $this->territoryRepository->findOneBy([
            'zip' => $this
                ->zipcodeProvider
                ->getZipCode($this->signalementDraftRequest->getAdresseLogementAdresseDetailCodePostal()),
        ]);

        $this->signalement = (new Signalement())
            ->setCreatedFrom($this->signalementDraft)
            ->setTerritory($territory)
            ->setIsCguAccepted(true)
            ->setReference($this->referenceGenerator->generate($territory))
            ->setDetails($this->signalementDraftRequest->getValidationSignalementOverviewMessageAdministration())
            ->setProfileDeclarant(ProfileDeclarant::from(strtoupper($this->signalementDraftRequest->getProfil())));

        return $this;
    }

    public function withAddressesCoordonees(): self
    {
        $this->setAddressData();
        $this->setOccupantDeclarantData();
        $this->setProprietaireData();

        return $this;
    }

    public function withTypeCompositionLogement(): self
    {
        $this->signalement
            ->setNbOccupantsLogement($this->signalementDraftRequest->getCompositionLogementNombrePersonnes())
            ->setNatureLogement($this->signalementDraftRequest->getTypeLogementNature())
            ->setTypeLogement($this->signalementDraftRequest->getTypeLogementNature())
            ->setSuperficie($this->signalementDraftRequest->getCompositionLogementSuperficie())
            ->setNbPiecesLogement($this->signalementDraftRequest->getCompositionLogementNbPieces())
            ->setTypeComposition(DataPropertyArrayFilter::filterByPrefix(
                $this->signalementDraft->getPayload(),
                SignalementDraftRequest::PREFIX_PROPERTIES_TYPE_COMPOSITION
            ))
            ->setIsBailEnCours($this->evalBoolean($this->signalementDraftRequest->getBailDpeDpe()))
            ->setDateEntree(new \DateTimeImmutable($this->signalementDraftRequest->getBailDpeDateEmmenagement()));

        return $this;
    }

    public function withSituationFoyer(): self
    {
        $this->signalement
            ->setIsRelogement($this->evalBoolean($this->signalementDraftRequest->getLogementSocialDemandeRelogement()))
            ->setIsAllocataire($this->evalBoolean($this->signalementDraftRequest->getLogementSocialAllocationCaisse()))
            ->setNumAllocataire($this->signalementDraftRequest->getLogementSocialMontantAllocation())
            ->setMontantAllocation($this->signalementDraftRequest->getLogementSocialMontantAllocation())
            ->setDateNaissanceOccupant(new \DateTimeImmutable($this->signalementDraftRequest->getLogementSocialDateNaissance()))
            ->setIsPreavisDepart($this->evalBoolean($this->signalementDraftRequest->getTravailleurSocialQuitteLogement()))
            ->setSituationFoyer(DataPropertyArrayFilter::filterByPrefix(
                $this->signalementDraft->getPayload(),
                SignalementDraftRequest::PREFIX_PROPERTIES_SITUATION_FOYER
            ));

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
            ->setIsProprioAverti($this->evalBoolean($this->signalementDraftRequest->getInfoProcedureBailleurPrevenu()))
            ->setIsRsa($this->evalBoolean($this->signalementDraftRequest->getInformationsComplementairesSituationOccupantsBeneficiaireRsa()))
            ->setIsFondSolidariteLogement($this->evalBoolean($this->signalementDraftRequest->getInformationsComplementairesSituationOccupantsBeneficiaireFsl()))
            ->setLoyer($this->signalementDraftRequest->getInformationsComplementairesLogementMontantLoyer())
            ->setNbNiveauxLogement($this->signalementDraftRequest->getInformationsComplementairesLogementNombreEtages())
            ->setInformationProcedure(DataPropertyArrayFilter::filterByPrefix(
                $this->signalementDraft->getPayload(),
                SignalementDraftRequest::PREFIX_PROPERTIES_INFORMATION_PROCEDURE
            )
            );

        return $this;
    }

    public function withInformationComplementaire(): self
    {
        $anneeConstruction = $this->signalementDraftRequest->getInformationsComplementairesLogementAnneeConstruction();
        $this->signalement
            ->setAnneeConstruction($anneeConstruction)
            ->setIsConstructionAvant1949($this->isConstructionAvant1949($anneeConstruction))
            ->setInformationComplementaire(DataPropertyArrayFilter::filterByPrefix(
                $this->signalementDraft->getPayload(),
                SignalementDraftRequest::PREFIX_PROPERTIES_INFORMATION_COMPLEMENTAIRE
            ));

        return $this;
    }

    public function build(): Signalement
    {
        return $this->signalement;
    }

    private function setAddressData(): void
    {
        $this->signalement
            ->setIsLogementSocial(
                $this->evalBoolean($this->signalementDraftRequest->getSignalementConcerneLogementSocialAutreTiers()))
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
    }

    private function setOccupantDeclarantData(): void
    {
        if ($this->isOccupant()) {
            $this->signalement
                ->setIsNotOccupant(false)
                ->setCiviiliteOccupant($this->signalementDraftRequest->getVosCoordonneesOccupantCivilite())
                ->setMailOccupant($this->signalementDraftRequest->getVosCoordonneesOccupantEmail())
                ->setNomOccupant($this->signalementDraftRequest->getVosCoordonneesOccupantNom())
                ->setPrenomOccupant($this->signalementDraftRequest->getVosCoordonneesOccupantPrenom())
                ->setTelOccupant($this->signalementDraftRequest->getVosCoordonneesOccupantTel())
                ->setTelOccupantBis($this->signalementDraftRequest->getVosCoordonneesOccupantTelSecondaire())
                ->setNomDeclarant($this->signalementDraftRequest->getVosCoordonneesOccupantNom())
                ->setPrenomDeclarant($this->signalementDraftRequest->getVosCoordonneesOccupantPrenom())
                ->setTelDeclarant($this->signalementDraftRequest->getVosCoordonneesOccupantTel())
                ->setTelDeclarantBis($this->signalementDraftRequest->getVosCoordonneesOccupantTelSecondaire())
                ->setMailDeclarant($this->signalementDraftRequest->getVosCoordonneesOccupantEmail());
        } else {
            $this->signalement
                ->setIsNotOccupant(true)
                ->setMailOccupant($this->signalementDraftRequest->getCoordonneesOccupantEmail())
                ->setNomOccupant($this->signalementDraftRequest->getCoordonneesOccupantNom())
                ->setPrenomOccupant($this->signalementDraftRequest->getCoordonneesOccupantPrenom())
                ->setTelOccupant($this->signalementDraftRequest->getCoordonneesOccupantTel())
                ->setTelOccupantBis($this->signalementDraftRequest->getCoordonneesOccupantTelSecondaire())
                ->setStructureDeclarant($this->signalementDraftRequest->getVosCoordonneesTiersNomOrganisme())
                ->setLienDeclarantOccupant($this->signalementDraftRequest->getVosCoordonneesTiersLien())
                ->setNomDeclarant($this->signalementDraftRequest->getVosCoordonneesTiersNom())
                ->setPrenomDeclarant($this->signalementDraftRequest->getVosCoordonneesTiersPrenom())
                ->setTelDeclarant($this->signalementDraftRequest->getVosCoordonneesTiersTel())
                ->setTelDeclarantBis($this->signalementDraftRequest->getVosCoordonneesTiersTelSecondaire())
                ->setMailDeclarant($this->signalementDraftRequest->getVosCoordonneesTiersEmail());
        }
    }

    private function setProprietaireData(): void
    {
        $this->signalement
            ->setAdresseProprio($this->signalementDraftRequest->getCoordonneesBailleurAdresse())
            ->setMailProprio($this->signalementDraftRequest->getCoordonneesBailleurEmail())
            ->setNomProprio($this->signalementDraftRequest->getCoordonneesBailleurNom())
            ->setPrenomProprio($this->signalementDraftRequest->getCoordonneesBailleurPrenom())
            ->setTelProprio($this->signalementDraftRequest->getCoordonneesBailleurTel());
    }

    private function isOccupant(): bool
    {
        return ProfileDeclarant::LOCATAIRE === $this->signalement->getProfileDeclarant()
            || ProfileDeclarant::BAILLEUR_OCCUPANT === $this->signalement->getProfileDeclarant();
    }

    private function evalBoolean(?string $value): ?bool
    {
        if (null === $value) {
            return null;
        }

        return 'oui' === $value;
    }

    private function isConstructionAvant1949(string $dateConstruction)
    {
        return new \DateTimeImmutable('01-01-1949') > new \DateTimeImmutable($dateConstruction);
    }
}
