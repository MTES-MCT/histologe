<?php

namespace App\Service\Signalement;

use App\Dto\Request\Signalement\SignalementDraftRequest;
use App\Entity\Enum\OccupantLink;
use App\Entity\Enum\ProfileDeclarant;
use App\Entity\Signalement;
use App\Entity\SignalementDraft;
use App\Factory\FileFactory;
use App\Factory\Signalement\InformationComplementaireFactory;
use App\Factory\Signalement\InformationProcedureFactory;
use App\Factory\Signalement\SituationFoyerFactory;
use App\Factory\Signalement\TypeCompositionLogementFactory;
use App\Repository\TerritoryRepository;
use App\Serializer\SignalementDraftRequestSerializer;
use App\Service\Token\TokenGeneratorInterface;
use App\Service\UploadHandlerService;
use App\Utils\Json;
use Symfony\Bundle\SecurityBundle\Security;

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
        private InformationComplementaireFactory $informationComplementaireFactory,
        private FileFactory $fileFactory,
        private UploadHandlerService $uploadHandlerService,
        private Security $security,
        private SignalementInputValueMapper $signalementInputValueMapper,
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
            ->setDetails($this->signalementDraftRequest->getMessageAdministration())
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
            ->setDateEntree($this->resolveDateEmmenagement());

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
            ->setDateNaissanceOccupant($this->resolveDateNaissanceOccupant());

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
        if ($this->isServiceSecours()) {
            return $this;
        }

        $anneeConstruction = $this->signalementDraftRequest->getInformationsComplementairesLogementAnneeConstruction();
        $this->signalement
            ->setInformationComplementaire($this->informationComplementaireFactory->createFromSignalementDraftPayload($this->payload))
            ->setAnneeConstruction($this->signalementDraftRequest->getInformationsComplementairesLogementAnneeConstruction())
            ->setIsPreavisDepart($this->evalBoolean($this->signalementDraftRequest->getInformationsComplementairesSituationOccupantsPreavisDepart()))
            ->setIsRelogement($this->isDemandeRelogement())
            ->setDateEntree($this->resolveDateEmmenagement())
            ->setIsConstructionAvant1949($this->isConstructionAvant1949($anneeConstruction));

        return $this;
    }

    public function withFiles(): self
    {
        if ($files = $this->signalementDraftRequest->getFiles()) {
            foreach ($files as $key => $fileList) {
                foreach ($fileList as $fileItem) {
                    $fileItem['slug'] = $key;
                    $file = $this->fileFactory->createFromFileArray(file: $fileItem);
                    $this->uploadHandlerService->moveFromBucketTempFolder($file->getFilename());
                    $file->setSize($this->uploadHandlerService->getFileSize($file->getFilename()));
                    $file->setIsVariantsGenerated($this->uploadHandlerService->hasVariants($file->getFilename()));
                    $this->signalement->addFile($file);
                }
            }
        }

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
                ->setTelOccupant(Json::encode($this->signalementDraftRequest->getVosCoordonneesOccupantTel()))
                ->setTelOccupantBis(Json::encode($this->signalementDraftRequest->getVosCoordonneesOccupantTelSecondaire()))
                ->setNomDeclarant($this->signalementDraftRequest->getVosCoordonneesOccupantNom())
                ->setPrenomDeclarant($this->signalementDraftRequest->getVosCoordonneesOccupantPrenom())
                ->setTelDeclarant(Json::encode($this->signalementDraftRequest->getVosCoordonneesOccupantTel()))
                ->setTelDeclarantSecondaire(Json::encode($this->signalementDraftRequest->getVosCoordonneesOccupantTelSecondaire()))
                ->setMailDeclarant($this->signalementDraftRequest->getVosCoordonneesOccupantEmail());
        } else {
            $this->signalement
                ->setIsNotOccupant(true)
                ->setMailOccupant($this->signalementDraftRequest->getCoordonneesOccupantEmail())
                ->setNomOccupant($this->signalementDraftRequest->getCoordonneesOccupantNom())
                ->setPrenomOccupant($this->signalementDraftRequest->getCoordonneesOccupantPrenom())
                ->setTelOccupant(Json::encode($this->signalementDraftRequest->getCoordonneesOccupantTel()))
                ->setTelOccupantBis(Json::encode($this->signalementDraftRequest->getCoordonneesOccupantTelSecondaire()))
                ->setStructureDeclarant($this->signalementDraftRequest->getVosCoordonneesTiersNomOrganisme())
                ->setLienDeclarantOccupant($this->resolveTiersLien())
                ->setNomDeclarant($this->signalementDraftRequest->getVosCoordonneesTiersNom())
                ->setPrenomDeclarant($this->signalementDraftRequest->getVosCoordonneesTiersPrenom())
                ->setTelDeclarant(Json::encode($this->signalementDraftRequest->getVosCoordonneesTiersTel()))
                ->setTelDeclarantSecondaire(Json::encode($this->signalementDraftRequest->getVosCoordonneesTiersTelSecondaire()))
                ->setMailDeclarant($this->signalementDraftRequest->getVosCoordonneesTiersEmail());
        }
    }

    private function setProprietaireData(): void
    {
        if ($this->isBailleurOccupant()) {
            $this->signalement
                ->setAdresseProprio($this->signalementDraftRequest->getAdresseLogementAdresse())
                ->setVilleProprio($this->signalementDraftRequest->getAdresseLogementAdresseDetailCommune())
                ->setCodePostalProprio($this->signalementDraftRequest->getAdresseLogementAdresseDetailCodePostal())
                ->setMailProprio($this->signalementDraftRequest->getVosCoordonneesOccupantEmail())
                ->setNomProprio($this->resolveNomProprioBailleurOccupant())
                ->setPrenomProprio($this->signalementDraftRequest->getVosCoordonneesOccupantPrenom())
                ->setTelProprio(Json::encode($this->signalementDraftRequest->getVosCoordonneesOccupantTel()))
                ->setTelProprioSecondaire(Json::encode($this->signalementDraftRequest->getVosCoordonneesOccupantTelSecondaire()));
        } elseif ($this->isBailleur()) {
            $this->signalement
                ->setStructureDeclarant($this->signalementDraftRequest->getVosCoordonneesTiersNomOrganisme())
                ->setNomProprio($this->signalementDraftRequest->getVosCoordonneesTiersNom())
                ->setPrenomProprio($this->signalementDraftRequest->getVosCoordonneesTiersPrenom())
                ->setTelProprio(Json::encode($this->signalementDraftRequest->getVosCoordonneesTiersTel()))
                ->setMailProprio($this->signalementDraftRequest->getVosCoordonneesTiersEmail())
                ->setTelProprioSecondaire(Json::encode($this->signalementDraftRequest->getVosCoordonneesTiersTelSecondaire()));
        } else {
            $this->signalement
                ->setAdresseProprio($this->signalementDraftRequest->getCoordonneesBailleurAdresseDetailNumero())
                ->setVilleProprio($this->signalementDraftRequest->getCoordonneesBailleurAdresseCommune())
                ->setCodePostalProprio($this->signalementDraftRequest->getCoordonneesBailleurAdresseDetailCodePostal())
                ->setMailProprio($this->signalementDraftRequest->getCoordonneesBailleurEmail())
                ->setNomProprio($this->signalementDraftRequest->getCoordonneesBailleurNom())
                ->setPrenomProprio($this->signalementDraftRequest->getCoordonneesBailleurPrenom())
                ->setTelProprio(Json::encode($this->signalementDraftRequest->getCoordonneesBailleurTel()))
                ->setTelProprioSecondaire(Json::encode($this->signalementDraftRequest->getCoordonneesBailleurTelSecondaire()));
        }
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

    private function isBailleurOccupant(): bool
    {
        return ProfileDeclarant::BAILLEUR_OCCUPANT === $this->signalement->getProfileDeclarant();
    }

    private function isBailleur(): bool
    {
        return ProfileDeclarant::BAILLEUR === $this->signalement->getProfileDeclarant();
    }

    private function isTiersPro(): bool
    {
        return ProfileDeclarant::TIERS_PRO === $this->signalement->getProfileDeclarant();
    }

    private function evalBoolean(?string $value): ?bool
    {
        if (null === $value) {
            return null;
        }

        return $this->signalementInputValueMapper->map($value);
    }

    private function isConstructionAvant1949(?string $dateConstruction): ?bool
    {
        if (empty($dateConstruction)) {
            return null;
        }

        return new \DateTimeImmutable('01-01-1949') > new \DateTimeImmutable($dateConstruction);
    }

    private function resolveDateEmmenagement(): ?\DateTimeImmutable
    {
        if (\in_array(
            $this->signalementDraft->getProfileDeclarant(),
            [ProfileDeclarant::LOCATAIRE, ProfileDeclarant::BAILLEUR_OCCUPANT]
        ) && !empty($dateEmmenagement = $this->signalementDraftRequest->getBailDpeDateEmmenagement())) {
            return new \DateTimeImmutable($dateEmmenagement);
        }

        if (!empty($dateEmmenagement = $this->signalementDraftRequest->getInformationsComplementairesSituationOccupantsDateEmmenagement())) {
            return new \DateTimeImmutable($dateEmmenagement);
        }

        return null;
    }

    private function isDemandeRelogement(): ?bool
    {
        if (ProfileDeclarant::LOCATAIRE === $this->signalementDraft->getProfileDeclarant()) {
            return $this->evalBoolean($this->signalementDraftRequest->getLogementSocialDemandeRelogement());
        }

        return $this->evalBoolean($this->signalementDraftRequest->getInformationsComplementairesSituationOccupantsDemandeRelogement());
    }

    private function resolveIsAllocataire(): ?string
    {
        if ($this->evalBoolean($this->signalementDraftRequest->getLogementSocialAllocation())) {
            return $this->signalementInputValueMapper->map(
                $this->signalementDraftRequest->getLogementSocialAllocationCaisse()
            );
        }

        return '0';
    }

    private function resolveTiersLien(): ?string
    {
        if ($this->isServiceSecours()) {
            return OccupantLink::SECOURS->name;
        } elseif ($this->isBailleur()) {
            return OccupantLink::BAILLEUR->name;
        } elseif ($this->isTiersPro()) {
            return OccupantLink::PRO->name;
        } elseif (ProfileDeclarant::TIERS_PARTICULIER !== $this->signalement->getProfileDeclarant()) {
            return null;
        }

        $tiersLien = OccupantLink::from(strtoupper($this->signalementDraftRequest->getVosCoordonneesTiersLien()));

        if (OccupantLink::VOISIN === $tiersLien) {
            return OccupantLink::VOISIN->name;
        }

        return $tiersLien->value;
    }

    private function resolveDateNaissanceOccupant(): ?\DateTimeImmutable
    {
        if (empty($this->signalementDraftRequest->getLogementSocialDateNaissance())) {
            return null;
        }

        return new \DateTimeImmutable($this->signalementDraftRequest->getLogementSocialDateNaissance());
    }

    private function resolveNomProprioBailleurOccupant(): ?string
    {
        return $this->signalementDraftRequest->getVosCoordonneesTiersNomOrganisme()
        ?? $this->signalementDraftRequest->getVosCoordonneesTiersNom();
    }
}
