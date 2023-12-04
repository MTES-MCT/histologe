<?php

namespace App\Service\Signalement;

use App\Dto\Request\Signalement\SignalementDraftRequest;
use App\Entity\Enum\OccupantLink;
use App\Entity\Enum\ProfileDeclarant;
use App\Entity\Model\SituationFoyer;
use App\Entity\Model\TypeCompositionLogement;
use App\Entity\Signalement;
use App\Entity\SignalementDraft;
use App\Factory\FileFactory;
use App\Factory\Signalement\InformationComplementaireFactory;
use App\Factory\Signalement\InformationProcedureFactory;
use App\Factory\Signalement\SituationFoyerFactory;
use App\Factory\Signalement\TypeCompositionLogementFactory;
use App\Repository\DesordreCategorieRepository;
use App\Repository\DesordreCritereRepository;
use App\Repository\DesordrePrecisionRepository;
use App\Repository\TerritoryRepository;
use App\Serializer\SignalementDraftRequestSerializer;
use App\Service\Signalement\DesordreTraitement\DesordreTraitementProcessor;
use App\Service\Token\TokenGeneratorInterface;
use App\Service\UploadHandlerService;
use App\Utils\DataPropertyArrayFilter;
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
        private DesordreCategorieRepository $desordreCategorieRepository,
        private DesordreCritereRepository $desordreCritereRepository,
        private DesordrePrecisionRepository $desordrePrecisionRepository,
        private DesordreTraitementProcessor $desordreTraitementProcessor,
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
        $categoryDisorders = $this->signalementDraftRequest->getCategorieDisorders();
        if (isset($categoryDisorders) && !empty($categoryDisorders)) {
            $this->processDesordresByZone('batiment');
            $this->processDesordresByZone('logement');
            $this->processDesordresTypeComposition();
        }

        // TODO : https://github.com/MTES-MCT/histologe/issues/1546

        // TODO : https://github.com/MTES-MCT/histologe/issues/1547

        return $this;
    }

    private function processDesordresByZone(string $zone)
    {
        $categoryDisorders = $this->signalementDraftRequest->getCategorieDisorders();
        if (isset($categoryDisorders[$zone]) && !empty($categoryDisorders[$zone])) {
            foreach ($categoryDisorders[$zone] as $categoryDisorderSlug) {
                // on récupère dans le draft toutes les infos liées à cette catégorie de désordres
                $filteredData = DataPropertyArrayFilter::filterByPrefix(
                    $this->payload,
                    [$categoryDisorderSlug]
                );

                // on récupère en base tous les critères de cette catégorie de désordres
                $criteres = $this->desordreCritereRepository->findBy(['slugCategorie' => $categoryDisorderSlug]);

                // chercher et lier les critères qu'on a dans le signalement
                $availableCritereSlugs = array_map(fn ($critere) => $critere->getSlugCritere(), $criteres);

                $critereSlugDraft = array_filter($filteredData, function ($value, $slug) use ($availableCritereSlugs) {
                    if (\in_array($slug, $availableCritereSlugs)) {
                        if (1 === $value) {
                            return true;
                        }
                    }

                    return false;
                }, \ARRAY_FILTER_USE_BOTH);

                $critereToLink = null;
                foreach ($critereSlugDraft as $slugCritere => $value) {
                    $critereToLink = $this->desordreCritereRepository->findOneBy(['slugCritere' => $slugCritere]);
                    $this->signalement->addDesordreCritere($critereToLink);
                    // on chercher les précisions qu'on peut lier
                    $precisions = $critereToLink->getDesordrePrecisions();
                    if (1 === \count($precisions)) {
                        // TODO : vérifier la valeur
                        // il n'y en a qu'une, on la lie
                        $this->signalement->addDesordrePrecision($precisions->first());
                    } else {
                        // passe par un service spécifique pour évaluer les précisions à ajouter sur ce critère
                        $desordrePrecisions = $this->desordreTraitementProcessor->process($critereToLink, $this->payload);
                        if (!$desordrePrecisions->isEmpty()) {
                            foreach ($desordrePrecisions as $desordrePrecision) {
                                if (null !== $desordrePrecision) {
                                    $this->signalement->addDesordrePrecision($desordrePrecision);
                                }
                                // TODO : loguer
                            }
                        }
                        // TODO : loguer
                    }
                    // TODO : gérer les fichiers associés s'il y en a
                }

                if (null !== $critereToLink) {
                    // lier la catégorie BO idoine
                    $this->signalement->addDesordreCategory($critereToLink->getDesordreCategorie());
                }
            }
        }
    }

    private function addDesordreCriterePrecisionBySlugs(string $slugCritere, string $slugPrecision)
    {
        $critereToLink = $this->desordreCritereRepository->findOneBy(['slugCritere' => $slugCritere]);
        if (null !== $critereToLink) {
            $this->signalement->addDesordreCritere($critereToLink);
        }
        $precisionToLink = $this->desordrePrecisionRepository->findOneBy(['desordrePrecisionSlug' => $slugPrecision]);
        if (null !== $precisionToLink) {
            $this->signalement->addDesordrePrecision($precisionToLink);
        }
    }

    private function processDesordresTypeComposition()
    {
        /** @var TypeCompositionLogement $typeCompositionLogement */
        $typeCompositionLogement = $this->typeCompositionLogementFactory->createFromSignalementDraftPayload($this->payload);

        if ('oui' === $typeCompositionLogement->getTypeLogementSousCombleSansFenetre()) {
            $this->addDesordreCriterePrecisionBySlugs(
                'desordres_type_composition_logement_sous_combles',
                'desordres_type_composition_logement_sous_combles'
            );
        }

        if ('oui' === $typeCompositionLogement->getTypeLogementSousSolSansFenetre()) {
            $this->addDesordreCriterePrecisionBySlugs(
                'desordres_type_composition_logement_sous_sol',
                'desordres_type_composition_logement_sous_sol'
            );
        }

        if ('piece_unique' === $typeCompositionLogement->getCompositionLogementPieceUnique()) {
            if ($typeCompositionLogement->getCompositionLogementSuperficie() < 9) {
                $this->addDesordreCriterePrecisionBySlugs(
                    'desordres_type_composition_logement_piece_unique',
                    'desordres_type_composition_logement_piece_unique_superficie'
                );
            }
            if ('oui' !== $typeCompositionLogement->getCompositionLogementHauteur()) {
                $this->addDesordreCriterePrecisionBySlugs(
                    'desordres_type_composition_logement_piece_unique',
                    'desordres_type_composition_logement_piece_unique_hauteur'
                );
            }
        } else {
            if ('oui' !== $typeCompositionLogement->getTypeLogementCommoditesPieceAVivre9m()) {
                $this->addDesordreCriterePrecisionBySlugs(
                    'desordres_type_composition_logement_plusieurs_pieces',
                    'desordres_type_composition_logement_plusieurs_pieces_aucune_piece_9'
                );
            }
            if ('oui' !== $typeCompositionLogement->getCompositionLogementHauteur()) {
                $this->addDesordreCriterePrecisionBySlugs(
                    'desordres_type_composition_logement_plusieurs_pieces',
                    'desordres_type_composition_logement_plusieurs_pieces_hauteur'
                );
            }
        }

        if ('oui' !== $typeCompositionLogement->getTypeLogementCommoditesCuisine()) {
            if ('oui' !== $typeCompositionLogement->getTypeLogementCommoditesCuisineCollective()) {
                $this->addDesordreCriterePrecisionBySlugs(
                    'desordres_type_composition_logement_cuisine',
                    'desordres_type_composition_logement_cuisine_collective_non'
                );
            } else {
                $this->addDesordreCriterePrecisionBySlugs(
                    'desordres_type_composition_logement_cuisine',
                    'desordres_type_composition_logement_cuisine_collective_oui'
                );
            }
        }

        if ('oui' !== $typeCompositionLogement->getTypeLogementCommoditesSalleDeBain()) {
            if ('oui' !== $typeCompositionLogement->getTypeLogementCommoditesSalleDeBainCollective()) {
                $this->addDesordreCriterePrecisionBySlugs(
                    'desordres_type_composition_logement_douche',
                    'desordres_type_composition_logement_douche_collective_non'
                );
            } else {
                $this->addDesordreCriterePrecisionBySlugs(
                    'desordres_type_composition_logement_douche',
                    'desordres_type_composition_logement_douche_collective_oui'
                );
            }
        }

        if ('oui' !== $typeCompositionLogement->getTypeLogementCommoditesWc()) {
            if ('oui' !== $typeCompositionLogement->getTypeLogementCommoditesWcCollective()) {
                $this->addDesordreCriterePrecisionBySlugs(
                    'desordres_type_composition_logement_wc',
                    'desordres_type_composition_logement_wc_collectif_non'
                );
            } else {
                $this->addDesordreCriterePrecisionBySlugs(
                    'desordres_type_composition_logement_wc',
                    'desordres_type_composition_logement_wc_collectif_oui'
                );
            }
        } else {
            if ('oui' === $typeCompositionLogement->getTypeLogementCommoditesWcCuisine()) {
                $this->addDesordreCriterePrecisionBySlugs(
                    'desordres_type_composition_logement_wc',
                    'desordres_type_composition_logement_wc_cuisine_ensemble'
                );
            }
        }
        /** @var SituationFoyer $situationFoyer */
        $situationFoyer = $this->situationFoyerFactory->createFromSignalementDraftPayload($this->payload);

        $nbOccupants = (int) ($typeCompositionLogement->getCompositionLogementNombrePersonnes());
        $surroccupation = false;
        if ('oui' === $situationFoyer->getLogementSocialAllocation()) {
            $superficie = $typeCompositionLogement->getCompositionLogementSuperficie();
            if (1 === $nbOccupants && $superficie >= 9) {
                $surroccupation = true;
            } elseif (2 === $nbOccupants && $superficie >= 16) {
                $surroccupation = true;
            } elseif ($nbOccupants > 2) {
                $superficieNecessaire = 16 + (($nbOccupants - 2) * 9);

                if ($superficie >= $superficieNecessaire) {
                    $surroccupation = true;
                }
            }
            if ($surroccupation) {
                $slugPrecionSurroccupation = 'desordres_type_composition_logement_suroccupation_allocataire';
            }
        } else {
            $nbPieces = $typeCompositionLogement->getCompositionLogementNbPieces();
            if ($nbPieces < 2 * $nbOccupants) {
                $surroccupation = true;
                $slugPrecionSurroccupation = 'desordres_type_composition_logement_suroccupation_non_allocataire';
            }
        }
        if ($surroccupation && isset($slugPrecionSurroccupation)) {
            $critereToLink = $this->desordreCritereRepository->findOneBy(
                ['slugCritere' => 'desordres_type_composition_logement_suroccupation']
            );
            if (null !== $critereToLink) {
                $this->signalement->addDesordreCritere($critereToLink);
            }
            $precisionToLink = $this->desordrePrecisionRepository->findOneBy(
                ['desordrePrecisionSlug' => $slugPrecionSurroccupation]
            );
            if (null !== $precisionToLink) {
                $this->signalement->addDesordrePrecision($precisionToLink);
            }
            // TODO taguer ces précisions comme "surroccupation" comme on le fait avec "is_danger"
        }
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
                ->setAdresseProprio($this->signalementDraftRequest->getAdresseLogementAdresse())
                ->setVilleProprio($this->signalementDraftRequest->getAdresseLogementAdresseDetailCommune())
                ->setCodePostalProprio($this->signalementDraftRequest->getAdresseLogementAdresseDetailCodePostal())
                ->setMailProprio($this->signalementDraftRequest->getVosCoordonneesOccupantEmail())
                ->setNomProprio($this->signalementDraftRequest->getVosCoordonneesOccupantNom())
                ->setPrenomProprio($this->signalementDraftRequest->getVosCoordonneesOccupantPrenom())
                ->setTelProprio($this->signalementDraftRequest->getVosCoordonneesOccupantTel())
                ->setTelProprioSecondaire($this->signalementDraftRequest->getVosCoordonneesOccupantTelSecondaire());
        } elseif ($this->isBailleur()) {
            $this->signalement
                ->setStructureDeclarant($this->signalementDraftRequest->getVosCoordonneesTiersNomOrganisme())
                ->setNomProprio($this->signalementDraftRequest->getVosCoordonneesTiersNom())
                ->setPrenomProprio($this->signalementDraftRequest->getVosCoordonneesTiersPrenom())
                ->setTelProprio($this->signalementDraftRequest->getVosCoordonneesTiersTel())
                ->setMailProprio($this->signalementDraftRequest->getVosCoordonneesTiersEmail())
                ->setTelProprioSecondaire($this->signalementDraftRequest->getVosCoordonneesTiersTelSecondaire());
        } else {
            $this->signalement
                ->setAdresseProprio($this->signalementDraftRequest->getCoordonneesBailleurAdresseDetailNumero())
                ->setVilleProprio($this->signalementDraftRequest->getCoordonneesBailleurAdresseCommune())
                ->setCodePostalProprio($this->signalementDraftRequest->getCoordonneesBailleurAdresseDetailCodePostal())
                ->setMailProprio($this->signalementDraftRequest->getCoordonneesBailleurEmail())
                ->setNomProprio($this->signalementDraftRequest->getCoordonneesBailleurNom())
                ->setPrenomProprio($this->signalementDraftRequest->getCoordonneesBailleurPrenom())
                ->setTelProprio($this->signalementDraftRequest->getCoordonneesBailleurTel())
                ->setTelProprioSecondaire($this->signalementDraftRequest->getCoordonneesBailleurTelSecondaire());
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

        if (empty($this->signalementDraftRequest->getVosCoordonneesTiersLien())) {
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
}
