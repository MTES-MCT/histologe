<?php

namespace App\Service\Signalement;

use App\Dto\Request\Signalement\SignalementDraftRequest;
use App\Entity\Enum\ChauffageType;
use App\Entity\Enum\DebutDesordres;
use App\Entity\Enum\OccupantLink;
use App\Entity\Enum\ProfileDeclarant;
use App\Entity\Enum\ProprioType;
use App\Entity\Model\TypeCompositionLogement;
use App\Entity\Signalement;
use App\Entity\SignalementDraft;
use App\Entity\Territory;
use App\Exception\Signalement\DesordreTraitementProcessorNotFound;
use App\Exception\Signalement\PrecisionNotFound;
use App\Factory\Signalement\InformationComplementaireFactory;
use App\Factory\Signalement\InformationProcedureFactory;
use App\Factory\Signalement\SituationFoyerFactory;
use App\Factory\Signalement\TypeCompositionLogementFactory;
use App\Manager\DesordreCritereManager;
use App\Repository\BailleurRepository;
use App\Repository\DesordreCritereRepository;
use App\Repository\DesordrePrecisionRepository;
use App\Serializer\SignalementDraftRequestSerializer;
use App\Service\Signalement\DesordreTraitement\DesordreCompositionLogementLoader;
use App\Service\Signalement\DesordreTraitement\DesordreTraitementProcessor;
use App\Service\Signalement\Qualification\SignalementQualificationUpdater;
use App\Specification\Signalement\SuroccupationSpecification;
use App\Utils\DataPropertyArrayFilter;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\TransactionRequiredException;

class SignalementBuilder
{
    private Signalement $signalement;
    private Territory $territory;
    private SignalementDraft $signalementDraft;
    private SignalementDraftRequest $signalementDraftRequest;
    /**
     * @var array<string>
     */
    private array $payload;

    public function __construct(
        private readonly BailleurRepository $bailleurRepository,
        private readonly ReferenceGenerator $referenceGenerator,
        private readonly SignalementDraftRequestSerializer $signalementDraftRequestSerializer,
        private readonly TypeCompositionLogementFactory $typeCompositionLogementFactory,
        private readonly SituationFoyerFactory $situationFoyerFactory,
        private readonly InformationProcedureFactory $informationProcedureFactory,
        private readonly InformationComplementaireFactory $informationComplementaireFactory,
        private readonly DesordreCritereRepository $desordreCritereRepository,
        private readonly DesordrePrecisionRepository $desordrePrecisionRepository,
        private readonly DesordreTraitementProcessor $desordreTraitementProcessor,
        private readonly DesordreCritereManager $desordreCritereManager,
        private readonly CriticiteCalculator $criticiteCalculator,
        private readonly SignalementQualificationUpdater $signalementQualificationUpdater,
        private readonly DesordreCompositionLogementLoader $desordreCompositionLogementLoader,
        private readonly ZipcodeProvider $zipcodeProvider,
    ) {
    }

    /**
     * @throws NonUniqueResultException
     * @throws TransactionRequiredException
     */
    public function createSignalementBuilderFrom(SignalementDraft $signalementDraft): self
    {
        $this->signalementDraft = $signalementDraft;

        $this->signalementDraftRequest = $this->signalementDraftRequestSerializer->denormalize(
            $this->payload = $signalementDraft->getPayload(),
            SignalementDraftRequest::class
        );
        $this->territory = $this->zipcodeProvider->getTerritoryByInseeCode($this->signalementDraftRequest->getAdresseLogementAdresseDetailInsee());

        $this->signalement = (new Signalement())
            ->setCreatedFrom($this->signalementDraft)
            ->setTerritory($this->territory)
            ->setIsCguAccepted(true)
            ->setReference($this->referenceGenerator->generate($this->territory))
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
        $nbOccupantsLogement = $this->convertStringToNumber($this->signalementDraftRequest->getCompositionLogementNombrePersonnes());
        $nbPiecesLogement = $this->convertStringToNumber($this->signalementDraftRequest->getCompositionLogementNbPieces());
        $this->signalement
            ->setTypeCompositionLogement(
                $this->typeCompositionLogementFactory->createFromSignalementDraftPayload($this->payload)
            )
            ->setNbOccupantsLogement($nbOccupantsLogement)
            ->setNatureLogement($this->signalementDraftRequest->getTypeLogementNature())
            ->setSuperficie((float) $this->signalementDraftRequest->getCompositionLogementSuperficie())
            ->setNbPiecesLogement($nbPiecesLogement)
            ->setIsBailEnCours($this->evalBoolean($this->signalementDraftRequest->getBailDpeBail()))
            ->setDateEntree($this->resolveDateEmmenagement())
            ->setNumeroInvariant($this->signalementDraftRequest->getBailDpeInvariant());

        return $this;
    }

    /**
     * @throws \DateMalformedStringException
     */
    public function withSituationFoyer(): self
    {
        $montantAllocation = $this->convertStringToNumber($this->signalementDraftRequest->getLogementSocialMontantAllocation(), false);
        $this->signalement
            ->setSituationFoyer($this->situationFoyerFactory->createFromSignalementDraftPayload($this->payload))
            ->setIsPreavisDepart(
                $this->evalBoolean($this->signalementDraftRequest->getTravailleurSocialPreavisDepart())
            )
            ->setIsAllocataire($this->resolveIsAllocataire())
            ->setNumAllocataire($this->signalementDraftRequest->getLogementSocialNumeroAllocataire())
            ->setMontantAllocation($montantAllocation)
            ->setDateNaissanceOccupant($this->resolveDateNaissanceOccupant());

        return $this;
    }

    /**
     * @throws DesordreTraitementProcessorNotFound
     * @throws PrecisionNotFound
     */
    public function withDesordres(): self
    {
        $categoryDisorders = $this->signalementDraftRequest->getCategorieDisorders();
        if (!empty($categoryDisorders)) {
            $this->processDesordresByZone('batiment');
            $this->processDesordresByZone('logement');
            $this->processDesordresTypeComposition();
        }
        // enregistre des données spécifiques dans jsonContent si elles existent
        // (slug du critère ou de la catégorie pour affichage)
        if (isset($this->payload['desordres_logement_nuisibles_autres_details_type_nuisibles'])) {
            $jsonContent['desordres_logement_nuisibles_autres'] =
            $this->payload['desordres_logement_nuisibles_autres_details_type_nuisibles'];
        }
        if (isset($this->payload['desordres_batiment_nuisibles_autres_details_type_nuisibles'])) {
            $jsonContent['desordres_batiment_nuisibles_autres'] =
            $this->payload['desordres_batiment_nuisibles_autres_details_type_nuisibles'];
        }
        if (isset($this->payload['desordres_logement_chauffage_type'])) {
            $chauffageType = ChauffageType::tryFrom(strtoupper($this->payload['desordres_logement_chauffage_type']));
            $jsonContent['desordres_logement_chauffage'] = $chauffageType->label();
        }

        if (isset($this->payload['desordres_logement_lumiere_plafond_trop_bas_piece_a_vivre_taille'])) {
            $jsonContent['desordres_logement_lumiere_plafond_trop_bas_piece_a_vivre'] =
                $this->payload['desordres_logement_lumiere_plafond_trop_bas_piece_a_vivre_taille'];
        }
        if (isset($this->payload['desordres_logement_lumiere_plafond_trop_bas_piece_a_vivre_taille_nsp'])) {
            $jsonContent['desordres_logement_lumiere_plafond_trop_bas_piece_a_vivre'] = 'Ne sait pas';
        }
        if (isset($this->payload['desordres_logement_lumiere_plafond_trop_bas_salle_de_bain_taille'])) {
            $jsonContent['desordres_logement_lumiere_plafond_trop_bas_salle_de_bain'] =
                $this->payload['desordres_logement_lumiere_plafond_trop_bas_salle_de_bain_taille'];
        }
        if (isset($this->payload['desordres_logement_lumiere_plafond_trop_bas_salle_de_bain_taille_nsp'])) {
            $jsonContent['desordres_logement_lumiere_plafond_trop_bas_salle_de_bain'] = 'Ne sait pas';
        }
        if (isset($this->payload['desordres_logement_lumiere_plafond_trop_bas_cuisine_taille'])) {
            $jsonContent['desordres_logement_lumiere_plafond_trop_bas_cuisine'] =
                $this->payload['desordres_logement_lumiere_plafond_trop_bas_cuisine_taille'];
        }
        if (isset($this->payload['desordres_logement_lumiere_plafond_trop_bas_cuisine_taille_nsp'])) {
            $jsonContent['desordres_logement_lumiere_plafond_trop_bas_cuisine'] = 'Ne sait pas';
        }

        if (isset($jsonContent)) {
            $this->signalement->setJsonContent($jsonContent);
        }

        if (!empty($this->signalementDraftRequest->getZoneConcerneeDebutDesordres())) {
            $this->signalement->setDebutDesordres(
                DebutDesordres::tryFrom(strtoupper($this->signalementDraftRequest->getZoneConcerneeDebutDesordres()))
            );
        }

        $this->signalement->setHasSeenDesordres(
            $this->evalBoolean($this->signalementDraftRequest->getZoneConcerneeConstatationDesordres())
        );

        $this->signalement->setScore($this->criticiteCalculator->calculate($this->signalement));

        $this->signalementQualificationUpdater->updateQualificationFromScore($this->signalement);

        return $this;
    }

    /**
     * @throws PrecisionNotFound
     * @throws DesordreTraitementProcessorNotFound
     */
    private function processDesordresByZone(string $zone): void
    {
        $categoryDisorders = $this->signalementDraftRequest->getCategorieDisorders();
        $desordreCriteresBySlug = $this->desordreCritereRepository->findAllByZoneIndexedBySlug($zone);
        if (!empty($categoryDisorders[$zone]) && \is_array($categoryDisorders[$zone])) {
            foreach ($categoryDisorders[$zone] as $categoryDisorderSlug) {
                // on récupère dans le draft toutes les infos liées à cette catégorie de désordres
                $filteredData = DataPropertyArrayFilter::filterByPrefix(
                    $this->payload,
                    [$categoryDisorderSlug]
                );

                // on récupère tous les slugs des critères de cette catégorie de désordres
                $availableCritereSlugs = array_filter(
                    array_keys($desordreCriteresBySlug),
                    fn ($slug) => $categoryDisorderSlug === $desordreCriteresBySlug[$slug]->getSlugCategorie()
                );

                $critereSlugDraft = $this->desordreCritereManager->getCriteresSlugsInDraft(
                    $filteredData,
                    $availableCritereSlugs
                );

                $critereToLink = null;
                foreach ($critereSlugDraft as $slugCritere => $value) {
                    $critereToLink = $desordreCriteresBySlug[$slugCritere];
                    // on cherche les précisions qu'on peut lier
                    $precisions = $critereToLink->getDesordrePrecisions();
                    if (1 === \count($precisions)) {
                        if (1 === $value) {
                            // il n'y en a qu'une, on la lie
                            $this->signalement->addDesordrePrecision($precisions->first());
                        }
                    } else {
                        // passe par un service spécifique pour évaluer les précisions à ajouter sur ce critère
                        $desordrePrecisions = $this->desordreTraitementProcessor->findDesordresPrecisionsBy(
                            $critereToLink,
                            $this->payload
                        );
                        if (null !== $desordrePrecisions) {
                            foreach ($desordrePrecisions as $desordrePrecision) {
                                if (null !== $desordrePrecision) {
                                    $this->signalement->addDesordrePrecision($desordrePrecision);
                                } else {
                                    throw new PrecisionNotFound($slugCritere, $this->signalementDraft->getId());
                                }
                            }
                        } else {
                            throw new DesordreTraitementProcessorNotFound($slugCritere, $this->signalementDraft->getId());
                        }
                    }
                }
            }
        }
    }

    private function processDesordresTypeComposition(): void
    {
        /** @var TypeCompositionLogement $typeCompositionLogement */
        $typeCompositionLogement = $this->typeCompositionLogementFactory->createFromSignalementDraftPayload(
            $this->payload
        );

        $this->desordreCompositionLogementLoader->load($this->signalement, $typeCompositionLogement);

        $situationFoyer = $this->situationFoyerFactory->createFromSignalementDraftPayload($this->payload);
        $suroccupationSpecification = new SuroccupationSpecification();
        if ($suroccupationSpecification->isSatisfiedBy($situationFoyer, $typeCompositionLogement)) {
            $precisionToLink = $this->desordrePrecisionRepository->findOneBy(
                ['desordrePrecisionSlug' => $suroccupationSpecification->getSlug()]
            );
            if (null !== $precisionToLink) {
                $this->signalement->addDesordrePrecision($precisionToLink);
            }
        }
    }

    public function withProcedure(): self
    {
        $loyer = $this->convertStringToNumber($this->signalementDraftRequest->getInformationsComplementairesLogementMontantLoyer(), false);
        $nbEtages = $this->convertStringToNumber($this->signalementDraftRequest->getInformationsComplementairesLogementNombreEtages());
        $this->signalement
            ->setInformationProcedure(
                $this->informationProcedureFactory->createFromSignalementDraftPayload($this->payload)
            )
            ->setIsProprioAverti($this->evalBoolean($this->signalementDraftRequest->getInfoProcedureBailleurPrevenu()))
            ->setLoyer($loyer)
            ->setNbNiveauxLogement($nbEtages)
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

    /**
     * @throws \DateMalformedStringException
     */
    public function withInformationComplementaire(): self
    {
        if ($this->isServiceSecours()) {
            return $this;
        }

        $anneeConstruction = $this->signalementDraftRequest->getInformationsComplementairesLogementAnneeConstruction();
        $this->signalement
            ->setInformationComplementaire(
                $this->informationComplementaireFactory->createFromSignalementDraftPayload($this->payload)
            )
            ->setAnneeConstruction(
                $this->signalementDraftRequest->getInformationsComplementairesLogementAnneeConstruction()
            )
            ->setIsRelogement($this->isDemandeRelogement())
            ->setDateEntree($this->resolveDateEmmenagement())
            ->setIsConstructionAvant1949($this->isConstructionAvant1949($anneeConstruction));

        return $this;
    }

    public function build(): ?Signalement
    {
        if ($this->signalement->getDesordrePrecisions()->isEmpty() && 0.0 === $this->signalement->getScore()) {
            return null;
        }

        return $this->signalement;
    }

    private function setAddressData(): void
    {
        $this->signalement
            ->setIsLogementSocial($this->isLogementSocial())
            ->setAdresseOccupant($this->signalementDraftRequest->getAdresseLogementAdresseDetailNumero())
            ->setCpOccupant($this->signalementDraftRequest->getAdresseLogementAdresseDetailCodePostal())
            ->setInseeOccupant($this->signalementDraftRequest->getAdresseLogementAdresseDetailInsee())
            ->setVilleOccupant($this->signalementDraftRequest->getAdresseLogementAdresseDetailCommune())
            ->setEtageOccupant($this->signalementDraftRequest->getAdresseLogementComplementAdresseEtage())
            ->setEscalierOccupant($this->signalementDraftRequest->getAdresseLogementComplementAdresseEscalier())
            ->setNumAppartOccupant(
                $this->signalementDraftRequest->getAdresseLogementComplementAdresseNumeroAppartement()
            )
            ->setAdresseAutreOccupant($this->signalementDraftRequest->getAdresseLogementComplementAdresseAutre())
            ->setManualAddressOccupant($this->signalementDraftRequest->getAdresseLogementAdresseDetailManual());
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
        if ($this->isBailleurOccupant()) {
            $this->signalement
                ->setStructureDeclarant($this->signalementDraftRequest->getVosCoordonneesOccupantNomOrganisme())
                ->setAdresseProprio($this->signalementDraftRequest->getAdresseLogementAdresse())
                ->setVilleProprio($this->signalementDraftRequest->getAdresseLogementAdresseDetailCommune())
                ->setCodePostalProprio($this->signalementDraftRequest->getAdresseLogementAdresseDetailCodePostal())
                ->setMailProprio($this->signalementDraftRequest->getVosCoordonneesOccupantEmail())
                ->setNomProprio($this->signalementDraftRequest->getVosCoordonneesOccupantNom())
                ->setPrenomProprio($this->signalementDraftRequest->getVosCoordonneesOccupantPrenom())
                ->setTelProprio($this->signalementDraftRequest->getVosCoordonneesOccupantTel())
                ->setTelProprioSecondaire($this->signalementDraftRequest->getVosCoordonneesOccupantTelSecondaire())
                ->setTypeProprio(
                    ProprioType::from(strtoupper(
                        $this->signalementDraftRequest->getSignalementConcerneProfilDetailBailleurProprietaire()
                    ))
                );
            if (ProprioType::ORGANISME_SOCIETE === $this->signalement->getTypeProprio()) {
                $this->signalement->setDenominationProprio($this->signalement->getNomProprio());
            }
        } elseif ($this->isBailleur()) {
            $this->signalement
                ->setStructureDeclarant($this->signalementDraftRequest->getVosCoordonneesTiersNomOrganisme())
                ->setNomProprio($this->signalementDraftRequest->getVosCoordonneesTiersNom())
                ->setPrenomProprio($this->signalementDraftRequest->getVosCoordonneesTiersPrenom())
                ->setTelProprio($this->signalementDraftRequest->getVosCoordonneesTiersTel())
                ->setMailProprio($this->signalementDraftRequest->getVosCoordonneesTiersEmail())
                ->setTelProprioSecondaire($this->signalementDraftRequest->getVosCoordonneesTiersTelSecondaire())
                ->setTypeProprio(
                    ProprioType::from(strtoupper(
                        $this->signalementDraftRequest->getSignalementConcerneProfilDetailBailleurBailleur()
                    ))
                );
            if (ProprioType::ORGANISME_SOCIETE === $this->signalement->getTypeProprio()) {
                $this->signalement->setDenominationProprio($this->signalement->getNomProprio());
            }
        } else {
            $this->signalement
                ->setAdresseProprio($this->signalementDraftRequest->getCoordonneesBailleurAdresseDetailNumero())
                ->setVilleProprio($this->signalementDraftRequest->getCoordonneesBailleurAdresseDetailCommune())
                ->setCodePostalProprio($this->signalementDraftRequest->getCoordonneesBailleurAdresseDetailCodePostal())
                ->setMailProprio($this->signalementDraftRequest->getCoordonneesBailleurEmail())
                ->setNomProprio($bailleurNom = $this->signalementDraftRequest->getCoordonneesBailleurNom())
                ->setPrenomProprio($this->signalementDraftRequest->getCoordonneesBailleurPrenom())
                ->setTelProprio($this->signalementDraftRequest->getCoordonneesBailleurTel())
                ->setTelProprioSecondaire($this->signalementDraftRequest->getCoordonneesBailleurTelSecondaire());

            if ($this->isLogementSocial() && $bailleurNom) {
                $bailleur = $this->bailleurRepository->findOneBailleurBy(
                    name: $bailleurNom,
                    territory: $this->territory,
                    bailleurSanitized: true
                );

                if (null !== $bailleur) {
                    $this->signalement->setBailleur($bailleur);
                }
                $this->signalement->setDenominationProprio($bailleurNom);
            }
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

    private function evalString(?bool $value): ?string
    {
        if (null === $value) {
            return null;
        }
        if (!$value) {
            return '0';
        }

        return (string) $value;
    }

    private function evalBoolean(?string $value): ?bool
    {
        if (null === $value || 'ne-sais-pas' === $value || 'nsp' === $value) {
            return null;
        }

        return SignalementInputValueMapper::map($value);
    }

    private function isConstructionAvant1949(?string $dateConstruction): ?bool
    {
        if (empty($dateConstruction)) {
            return null;
        }

        return '1949' > $dateConstruction;
    }

    /**
     * @throws \DateMalformedStringException
     */
    private function resolveDateEmmenagement(): ?\DateTimeImmutable
    {
        if (\in_array(
            $this->signalementDraft->getProfileDeclarant(),
            [ProfileDeclarant::LOCATAIRE, ProfileDeclarant::BAILLEUR_OCCUPANT]
        ) && !empty($dateEmmenagement = $this->signalementDraftRequest->getBailDpeDateEmmenagement())) {
            return new \DateTimeImmutable($dateEmmenagement);
        }

        if (!empty($dateEmmenagement
            = $this->signalementDraftRequest->getInformationsComplementairesSituationOccupantsDateEmmenagement())) {
            return new \DateTimeImmutable($dateEmmenagement);
        }

        return null;
    }

    private function isDemandeRelogement(): ?bool
    {
        if (ProfileDeclarant::LOCATAIRE === $this->signalementDraft->getProfileDeclarant()
            || ProfileDeclarant::BAILLEUR_OCCUPANT === $this->signalementDraft->getProfileDeclarant()) {
            return $this->evalBoolean($this->signalementDraftRequest->getLogementSocialDemandeRelogement());
        }

        return $this->evalBoolean(
            $this->signalementDraftRequest->getInformationsComplementairesSituationOccupantsDemandeRelogement()
        );
    }

    private function resolveIsAllocataire(): ?string
    {
        if ($this->evalBoolean($this->signalementDraftRequest->getLogementSocialAllocation())
            && $this->evalBoolean($this->signalementDraftRequest->getLogementSocialAllocationCaisse())) {
            return SignalementInputValueMapper::map($this->signalementDraftRequest->getLogementSocialAllocationCaisse());
        }

        return $this->evalString(SignalementInputValueMapper::map($this->signalementDraftRequest->getLogementSocialAllocation()));
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

        if (empty($this->signalementDraftRequest->getVosCoordonneesTiersLien())) {
            return null;
        }

        $tiersLien = OccupantLink::from(strtoupper($this->signalementDraftRequest->getVosCoordonneesTiersLien()));

        if (OccupantLink::VOISIN === $tiersLien) {
            return OccupantLink::VOISIN->name;
        }

        return $tiersLien->value;
    }

    /**
     * @throws \DateMalformedStringException
     */
    private function resolveDateNaissanceOccupant(): ?\DateTimeImmutable
    {
        if (empty($this->signalementDraftRequest->getLogementSocialDateNaissance())) {
            return null;
        }

        return new \DateTimeImmutable($this->signalementDraftRequest->getLogementSocialDateNaissance());
    }

    private function isLogementSocial(): ?bool
    {
        return $this->isServiceSecours()
            ? $this->evalBoolean($this->signalementDraftRequest->getSignalementConcerneLogementSocialServiceSecours())
            : $this->evalBoolean($this->signalementDraftRequest->getSignalementConcerneLogementSocialAutreTiers());
    }

    private function convertStringToNumber(?string $value, bool $returnInt = true): float|int|null
    {
        if (null === $value || '' === $value) {
            return null;
        }

        if ($returnInt) {
            return (int) preg_replace('/[^0-9]+/', '', $value);
        }

        return (float) preg_replace('/[^0-9.]+/', '', $value);
    }
}
