<?php

namespace App\Service\Signalement;

use App\Dto\Request\Signalement\SignalementDraftRequest;
use App\Entity\Enum\OccupantLink;
use App\Entity\Enum\ProfileDeclarant;
use App\Entity\Signalement;
use App\Entity\SignalementDraft;
use App\Factory\Signalement\InformationComplementaireFactory;
use App\Factory\Signalement\InformationProcedureFactory;
use App\Factory\Signalement\SituationFoyerFactory;
use App\Factory\Signalement\TypeCompositionLogementFactory;
use App\Repository\TerritoryRepository;
use App\Serializer\SignalementDraftRequestSerializer;
use App\Service\Token\TokenGeneratorInterface;

class SignalementBuilder
{
    private Signalement $signalement;
    private SignalementDraft $signalementDraft;
    private SignalementDraftRequest $signalementDraftRequest;
    private array $payload;

    public function __construct(
        private TerritoryRepository $territoryRepository,
        private ZipcodeProvider $zipcodeProvider,
        private ReferenceGenerator $referenceGenerator,
        private TokenGeneratorInterface $tokenGenerator,
        private SignalementDraftRequestSerializer $signalementDraftRequestSerializer,
        private TypeCompositionLogementFactory $typeCompositionLogementFactory,
        private SituationFoyerFactory $situationFoyerFactory,
        private InformationProcedureFactory $informationProcedureFactory,
        private InformationComplementaireFactory $informationComplementaireFactory
    ) {
    }

    public function createSignalementBuilderFrom(SignalementDraft $signalementDraft): self
    {
        $this->signalementDraft = $signalementDraft;

        $this->signalementDraftRequest = $this->signalementDraftRequestSerializer->denormalize(
            $this->payload = $signalementDraft->getPayload(),
            SignalementDraftRequest::class
        );

        $territory = $this->territoryRepository->findOneBy([
            'zip' => $this
                ->zipcodeProvider
                ->getZipCode($this->signalementDraftRequest->getAdresseLogementAdresseDetailCodePostal()),
        ]);

        $this->signalement = (new Signalement())
            ->setCreatedFrom($this->signalementDraft)
            ->setCodeSuivi($this->tokenGenerator->generateToken())
            ->setTerritory($territory)
            ->setIsCguAccepted(true)
            ->setReference($this->referenceGenerator->generate($territory))
            ->setDetails($this->signalementDraftRequest->getValidationSignalementOverviewMessageAdministration())
            ->setProfileDeclarant(ProfileDeclarant::from(strtoupper($this->signalementDraftRequest->getProfil())));

        return $this;
    }

    public function withAdressesCoordonnees(): self
    {
        $this->setAddressData();
        $this->setOccupantDeclarantData();
        $this->setProprietaireData();

        return $this;
    }

    public function withTypeCompositionLogement(): self
    {
        $this->signalement
            ->setTypeCompositionLogement($this->typeCompositionLogementFactory->createFromSignalementDraftPayload($this->payload))
            ->setNbOccupantsLogement($this->signalementDraftRequest->getCompositionLogementNombrePersonnes())
            ->setNatureLogement($this->signalementDraftRequest->getTypeLogementNature())
            ->setTypeLogement($this->signalementDraftRequest->getTypeLogementNature())
            ->setSuperficie($this->signalementDraftRequest->getCompositionLogementSuperficie())
            ->setNbPiecesLogement($this->signalementDraftRequest->getCompositionLogementNbPieces())
            ->setIsBailEnCours($this->evalBoolean($this->signalementDraftRequest->getBailDpeBail()))
            ->setDateEntree(new \DateTimeImmutable($this->signalementDraftRequest->getBailDpeDateEmmenagement()));

        return $this;
    }

    public function withSituationFoyer(): self
    {
        $this->signalement
            ->setSituationFoyer($this->situationFoyerFactory->createFromSignalementDraftPayload($this->payload))
            ->setIsRelogement($this->evalBoolean($this->signalementDraftRequest->getLogementSocialDemandeRelogement()))
            ->setIsAllocataire($this->resolveIsAllocataire())
            ->setNumAllocataire($this->signalementDraftRequest->getLogementSocialNumeroAllocataire())
            ->setMontantAllocation($this->signalementDraftRequest->getLogementSocialMontantAllocation())
            ->setDateNaissanceOccupant(
                new \DateTimeImmutable($this->signalementDraftRequest->getLogementSocialDateNaissance())
            );

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
            ->setInformationProcedure($this->informationProcedureFactory->createFromSignalementDraftPayload($this->payload))
            ->setIsProprioAverti($this->evalBoolean($this->signalementDraftRequest->getInfoProcedureBailleurPrevenu()))
            ->setLoyer($this->signalementDraftRequest->getInformationsComplementairesLogementMontantLoyer())
            ->setNbNiveauxLogement($this->signalementDraftRequest->getInformationsComplementairesLogementNombreEtages())
            ->setIsFondSolidariteLogement(
                $this->evalBoolean(
                    $this->signalementDraftRequest->getInformationsComplementairesSituationOccupantsBeneficiaireFsl()
                )
            )
            ->setIsRsa(
                $this->evalBoolean(
                    $this->signalementDraftRequest->getInformationsComplementairesSituationOccupantsBeneficiaireRsa()
                )
            );

        return $this;
    }

    public function withInformationComplementaire(): self
    {
        $anneeConstruction = $this->signalementDraftRequest->getInformationsComplementairesLogementAnneeConstruction();
        $this->signalement
            ->setInformationComplementaire($this->informationComplementaireFactory->createFromSignalementDraftPayload($this->payload))
            ->setAnneeConstruction($this->signalementDraftRequest->getInformationsComplementairesLogementAnneeConstruction())
            ->setIsConstructionAvant1949($this->isConstructionAvant1949($anneeConstruction));

        return $this;
    }

    public function build(): Signalement
    {
        return $this->signalement;
    }

    private function setAddressData(): void
    {
        $isLogementSocial = $this->isServiceSecours()
            ? $this->evalBoolean($this->signalementDraftRequest->getSignalementConcerneLogementSocialServiceSecours())
            : $this->evalBoolean($this->signalementDraftRequest->getSignalementConcerneLogementSocialAutreTiers());

        $this->signalement
            ->setIsLogementSocial($isLogementSocial)
            ->setAdresseOccupant($this->signalementDraftRequest->getAdresseLogementAdresseDetailNumero())
            ->setCpOccupant($this->signalementDraftRequest->getAdresseLogementAdresseDetailCodePostal())
            ->setInseeOccupant($this->signalementDraftRequest->getAdresseLogementAdresseDetailInsee())
            ->setGeoloc([
                'lat' => $this->signalementDraftRequest->getAdresseLogementAdresseDetailGeolocLat(),
                'lng' => $this->signalementDraftRequest->getAdresseLogementAdresseDetailGeolocLng(),
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
                ->setCiviliteOccupant($this->signalementDraftRequest->getVosCoordonneesOccupantCivilite())
                ->setMailOccupant($this->signalementDraftRequest->getVosCoordonneesOccupantEmail())
                ->setNomOccupant($this->signalementDraftRequest->getVosCoordonneesOccupantNom())
                ->setPrenomOccupant($this->signalementDraftRequest->getVosCoordonneesOccupantPrenom())
                ->setTelOccupant($this->signalementDraftRequest->getVosCoordonneesOccupantTel())
                ->setTelOccupantBis($this->signalementDraftRequest->getVosCoordonneesOccupantTelSecondaire())
                ->setNomDeclarant($this->signalementDraftRequest->getVosCoordonneesOccupantNom())
                ->setPrenomDeclarant($this->signalementDraftRequest->getVosCoordonneesOccupantPrenom())
                ->setTelDeclarant($this->signalementDraftRequest->getVosCoordonneesOccupantTel())
                ->setTelDeclarantSecondaire($this->signalementDraftRequest->getVosCoordonneesOccupantTelSecondaire())
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
                ->setLienDeclarantOccupant($this->resolveTiersLien())
                ->setNomDeclarant($this->signalementDraftRequest->getVosCoordonneesTiersNom())
                ->setPrenomDeclarant($this->signalementDraftRequest->getVosCoordonneesTiersPrenom())
                ->setTelDeclarant($this->signalementDraftRequest->getVosCoordonneesTiersTel())
                ->setTelDeclarantSecondaire($this->signalementDraftRequest->getVosCoordonneesTiersTelSecondaire())
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

    private function isServiceSecours(): bool
    {
        return ProfileDeclarant::SERVICE_SECOURS === $this->signalement->getProfileDeclarant();
    }

    private function evalBoolean(?string $value): ?bool
    {
        if (null === $value) {
            return null;
        }

        return 'oui' === $value;
    }

    private function isConstructionAvant1949(?string $dateConstruction): ?bool
    {
        if (empty($dateConstruction)) {
            return null;
        }

        return new \DateTimeImmutable('01-01-1949') > new \DateTimeImmutable($dateConstruction);
    }

    private function resolveIsAllocataire(): ?string
    {
        if ($this->evalBoolean($this->signalementDraftRequest->getLogementSocialAllocation())) {
            return strtolower($this->signalementDraftRequest->getLogementSocialAllocationCaisse());
        }

        return '0';
    }

    private function resolveTiersLien(): string
    {
        $tiersLien = OccupantLink::from(strtoupper($this->signalementDraftRequest->getVosCoordonneesTiersLien()));

        if (OccupantLink::VOISINAGE === $tiersLien) {
            return OccupantLink::VOISINAGE->label();
        }

        return $tiersLien->value;
    }
}
