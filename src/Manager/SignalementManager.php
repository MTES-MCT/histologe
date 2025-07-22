<?php

namespace App\Manager;

use App\Dto\Request\Signalement\AdresseOccupantRequest;
use App\Dto\Request\Signalement\CompositionLogementRequest;
use App\Dto\Request\Signalement\CoordonneesAgenceRequest;
use App\Dto\Request\Signalement\CoordonneesBailleurRequest;
use App\Dto\Request\Signalement\CoordonneesFoyerRequest;
use App\Dto\Request\Signalement\CoordonneesTiersRequest;
use App\Dto\Request\Signalement\InformationsLogementRequest;
use App\Dto\Request\Signalement\ProcedureDemarchesRequest;
use App\Dto\Request\Signalement\QualificationNDERequest;
use App\Dto\Request\Signalement\SituationFoyerRequest;
use App\Dto\SignalementAffectationClose;
use App\Dto\SignalementAffectationListView;
use App\Entity\Affectation;
use App\Entity\Enum\AffectationStatus;
use App\Entity\Enum\MotifCloture;
use App\Entity\Enum\ProfileDeclarant;
use App\Entity\Enum\ProprioType;
use App\Entity\Enum\Qualification;
use App\Entity\Enum\SignalementStatus;
use App\Entity\Enum\SuiviCategory;
use App\Entity\Model\InformationComplementaire;
use App\Entity\Model\InformationProcedure;
use App\Entity\Model\SituationFoyer;
use App\Entity\Model\TypeCompositionLogement;
use App\Entity\Partner;
use App\Entity\Signalement;
use App\Entity\SignalementQualification;
use App\Entity\Suivi;
use App\Entity\Territory;
use App\Entity\User;
use App\Event\SignalementCreatedEvent;
use App\Factory\SignalementAffectationListViewFactory;
use App\Factory\SignalementExportFactory;
use App\Factory\SignalementFactory;
use App\Repository\AffectationRepository;
use App\Repository\BailleurRepository;
use App\Repository\DesordrePrecisionRepository;
use App\Repository\PartnerRepository;
use App\Repository\SignalementRepository;
use App\Service\Gouv\Ban\Response\Address;
use App\Service\Signalement\CriticiteCalculator;
use App\Service\Signalement\DesordreTraitement\DesordreCompositionLogementLoader;
use App\Service\Signalement\Qualification\QualificationStatusService;
use App\Service\Signalement\Qualification\SignalementQualificationUpdater;
use App\Service\Signalement\SignalementAddressUpdater;
use App\Service\Signalement\SignalementInputValueMapper;
use App\Service\Signalement\ZipcodeProvider;
use App\Specification\Signalement\SuroccupationSpecification;
use Doctrine\DBAL\Exception;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerInterface;

class SignalementManager extends AbstractManager
{
    public function __construct(
        protected ManagerRegistry $managerRegistry,
        private readonly Security $security,
        private readonly SignalementFactory $signalementFactory,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly QualificationStatusService $qualificationStatusService,
        private readonly SignalementAffectationListViewFactory $signalementAffectationListViewFactory,
        private readonly SignalementExportFactory $signalementExportFactory,
        private readonly SuroccupationSpecification $suroccupationSpecification,
        private readonly CriticiteCalculator $criticiteCalculator,
        private readonly SignalementQualificationUpdater $signalementQualificationUpdater,
        private readonly DesordrePrecisionRepository $desordrePrecisionRepository,
        private readonly DesordreCompositionLogementLoader $desordreCompositionLogementLoader,
        private readonly SuiviManager $suiviManager,
        private readonly BailleurRepository $bailleurRepository,
        private readonly AffectationRepository $affectationRepository,
        private readonly SignalementAddressUpdater $signalementAddressUpdater,
        private readonly ZipcodeProvider $zipcodeProvider,
        #[Autowire(service: 'html_sanitizer.sanitizer.app.message_sanitizer')]
        private readonly HtmlSanitizerInterface $htmlSanitizer,
        string $entityName = Signalement::class,
    ) {
        parent::__construct($managerRegistry, $entityName);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function createOrUpdate(Territory $territory, array $data, bool $isImported = false): ?Signalement
    {
        /** @var Signalement|null $signalement */
        $signalement = $this->getRepository()->findOneBy([
            'territory' => $territory,
            'reference' => $data['reference'],
        ]);

        if ($signalement instanceof Signalement) {
            return $this->update($signalement, $data);
        }

        $signalement = $this->signalementFactory->createInstanceFrom($territory, $data, $isImported);
        if (!$isImported) {
            $this->eventDispatcher->dispatch(new SignalementCreatedEvent($signalement), SignalementCreatedEvent::NAME);
        }

        return $signalement;
    }

    /**
     * @param array<string, mixed> $data
     */
    public function update(Signalement $signalement, array $data): Signalement
    {
        if (empty($data['statut'])) {
            $data['statut'] = SignalementStatus::ACTIVE;
            if ($data['motifCloture'] || $data['closedAt']) {
                $data['statut'] = SignalementStatus::CLOSED;
            }
        }

        return $signalement
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
            ->setIsRefusIntervention($data['isRefusIntervention'])
            ->setRaisonRefusIntervention($data['raisonRefusIntervention'])
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
            ->setAdresseOccupant($data['adresseOccupant'])
            ->setCpOccupant($data['cpOccupant'])
            ->setVilleOccupant($data['villeOccupant'])
            ->setIsCguAccepted((bool) $data['isCguAccepted'])
            ->setCreatedAt($data['createdAt'])
            ->setModifiedAt(new \DateTimeImmutable())
            ->setStatut($data['statut'])
            ->setValidatedAt(
                SignalementStatus::ACTIVE === $data['statut'] ? $data['createdAt'] : new \DateTimeImmutable()
            )
            ->setReference($data['reference'])
            ->setMontantAllocation((float) $data['montantAllocation'])
            ->setCodeProcedure($data['codeProcedure'])
            ->setEtageOccupant($data['etageOccupant'])
            ->setEscalierOccupant($data['escalierOccupant'])
            ->setNumAppartOccupant($data['numAppartOccupant'])
            ->setAdresseAutreOccupant($data['adresseAutreOccupant'])
            ->setInseeOccupant($data['inseeOccupant'])
            ->setLienDeclarantOccupant($data['lienDeclarantOccupant'])
            ->setIsConsentementTiers((bool) $data['isConsentementTiers'])
            ->setIsRsa((bool) $data['isRsa'])
            ->setAnneeConstruction($data['anneeConstruction'])
            ->setTypeEnergieLogement($data['typeEnergieLogement'])
            ->setOrigineSignalement($data['origineSignalement'])
            ->setSituationOccupant($data['situationOccupant'])
            ->setSituationProOccupant($data['situationProOccupant'])
            ->setNaissanceOccupants($data['naissanceOccupants'])
            ->setIsLogementCollectif((bool) $data['isLogementCollectif'])
            ->setIsConstructionAvant1949((bool) $data['isConstructionAvant1949'])
            ->setIsRisqueSurOccupation((bool) $data['isRisqueSurOccupation'])
            ->setProprioAvertiAt($data['prorioAvertiAt'])
            ->setNomReferentSocial($data['nomReferentSocial'])
            ->setStructureReferentSocial($data['StructureReferentSocial'])
            ->setNumeroInvariant($data['numeroInvariant'])
            ->setNbPiecesLogement((int) $data['nbPiecesLogement'])
            ->setNbChambresLogement((int) $data['nbChambresLogement'])
            ->setNbNiveauxLogement((int) $data['nbNiveauxLogement'])
            ->setNbOccupantsLogement((int) $data['nbOccupantsLogement'])
            ->setMotifCloture(
                null !== $data['motifCloture']
                    ? MotifCloture::tryFrom($data['motifCloture'])
                    : null
            )
            ->setClosedAt($data['closedAt'])
            ->setIsFondSolidariteLogement((bool) $data['isFondSolidariteLogement']);
    }

    public function updateAddressOccupantFromAddress(Signalement $signalement, Address $address): void
    {
        $signalement->setInseeOccupant($address->getInseeCode());

        if (empty($signalement->getCpOccupant())) {
            $signalement->setCpOccupant($address->getZipCode());
        }
    }

    /**
     * @return array<string, mixed>
     *
     * @throws Exception
     */
    public function findAllPartners(Signalement $signalement): array
    {
        /** @var PartnerRepository $partnerRepository */
        $partnerRepository = $this->managerRegistry->getRepository(Partner::class);
        $partners['affected'] = $partnerRepository->findByLocalization(signalement: $signalement);
        $partners['not_affected'] = $partnerRepository->findByLocalization(
            signalement: $signalement,
            affected: false
        );

        return $partners;
    }

    /**
     * @return array<int, int>
     */
    public function findPartners(Signalement $signalement): array
    {
        $affectation = $signalement->getAffectations()->map(
            function (Affectation $affectation) {
                return $affectation->getPartner()->getId();
            }
        );

        return $affectation->toArray();
    }

    public function closeSignalementForAllPartners(SignalementAffectationClose $signalementAffectationClose): Signalement
    {
        $signalement = $signalementAffectationClose->getSignalement();
        $signalement
            ->setStatut(SignalementStatus::CLOSED)
            ->setMotifCloture($signalementAffectationClose->getMotifCloture())
            ->setClosedAt(new \DateTimeImmutable())
            ->setComCloture($this->htmlSanitizer->sanitize($signalementAffectationClose->getDescription()));

        /** @var User $user */
        $user = $this->security->getUser();
        $this->affectationRepository->closeBySignalement($signalement, $signalementAffectationClose->getMotifCloture(), $user);
        $this->managerRegistry->getManager()->flush();

        return $signalement;
    }

    /**
     * @return array<int, User>
     */
    public function findUsersAffectedToSignalement(
        Signalement $signalement,
        AffectationStatus $statusAffectation,
        ?Partner $partnerToExclude,
    ): array {
        $list = [];
        $affectations = $signalement->getAffectations();
        foreach ($affectations as $affectation) {
            $partner = $affectation->getPartner();
            if ((null === $partnerToExclude || $partnerToExclude != $partner) && $affectation->getStatut() === $statusAffectation->value) {
                $list = array_merge($list, $partner->getUsers()->toArray());
            }
        }

        return array_unique($list, \SORT_REGULAR);
    }

    /**
     * @throws \DateMalformedStringException
     */
    public function updateFromSignalementQualification(
        SignalementQualification $signalementQualification,
        QualificationNDERequest $qualificationNDERequest,
    ): void {
        $signalement = $signalementQualification->getSignalement();
        // mise à jour du signalement
        if ($qualificationNDERequest->getDateEntree() || $qualificationNDERequest->getClasseEnergetique()) {
            $typeCompositionLogement = new TypeCompositionLogement();
            if (!empty($signalement->getTypeCompositionLogement())) {
                $typeCompositionLogement = clone $signalement->getTypeCompositionLogement();
            }
            if ($qualificationNDERequest->getDateEntree()) {
                $signalement->setDateEntree(new \DateTimeImmutable($qualificationNDERequest->getDateEntree()));
                $typeCompositionLogement
                    ->setBailDpeDateEmmenagement($qualificationNDERequest->getDateEntree());
            }
            if ($qualificationNDERequest->getClasseEnergetique()) {
                $typeCompositionLogement
                    ->setBailDpeClasseEnergetique($qualificationNDERequest->getClasseEnergetique());
            }
            $signalement->setTypeCompositionLogement($typeCompositionLogement);
        }

        if (null !== $qualificationNDERequest->getSuperficie()
            && $signalement->getSuperficie() !== $qualificationNDERequest->getSuperficie()
        ) {
            $signalement->setSuperficie($qualificationNDERequest->getSuperficie());
        }
        $this->save($signalement);

        $signalementQualification->setDetails($qualificationNDERequest->getDetails());
        $this->save($signalementQualification);

        $signalementQualification->setStatus(
            $this->qualificationStatusService->getNDEStatus($signalementQualification)
        );
        $this->save($signalementQualification);

        $typeCompositionLogement = new TypeCompositionLogement();
        if (!empty($signalement->getTypeCompositionLogement())) {
            $typeCompositionLogement = clone $signalement->getTypeCompositionLogement();
        }
        switch ($qualificationNDERequest->getDetails()['DPE']) {
            case true:
                $typeCompositionLogement->setBailDpeDpe('oui');
                break;
            case false:
                $typeCompositionLogement->setBailDpeDpe('non');
                break;
            default:
                $typeCompositionLogement->setBailDpeDpe('nsp');
                break;
        }
        $signalement->setTypeCompositionLogement($typeCompositionLogement);
        $this->save($signalement);
    }

    public function updateFromAdresseOccupantRequest(
        Signalement $signalement,
        AdresseOccupantRequest $adresseOccupantRequest,
    ): bool {
        $signalement->setAdresseOccupant($adresseOccupantRequest->getAdresse())
            ->setCpOccupant($adresseOccupantRequest->getCodePostal())
            ->setVilleOccupant($adresseOccupantRequest->getVille())
            ->setInseeOccupant($adresseOccupantRequest->getInsee())

            ->setEtageOccupant($adresseOccupantRequest->getEtage())
            ->setEscalierOccupant($adresseOccupantRequest->getEscalier())
            ->setNumAppartOccupant($adresseOccupantRequest->getNumAppart())
            ->setAdresseAutreOccupant($adresseOccupantRequest->getAutre())
            ->setManualAddressOccupant('1' === $adresseOccupantRequest->getManual());

        $this->signalementAddressUpdater->updateAddressOccupantFromBanData(signalement: $signalement);

        $this->save($signalement);

        return $this->suiviManager->addSuiviIfNeeded(
            signalement: $signalement,
            description: 'L\'adresse du logement a été modifiée par '
        );
    }

    public function updateFromCoordonneesTiersRequest(
        Signalement $signalement,
        CoordonneesTiersRequest $coordonneesTiersRequest,
    ): bool {
        if (ProfileDeclarant::BAILLEUR == $signalement->getProfileDeclarant()) {
            $signalement
                ->setTypeProprio(
                    $coordonneesTiersRequest->getTypeProprio()
                    ? ProprioType::from($coordonneesTiersRequest->getTypeProprio())
                    : null
                );
        }
        $signalement->setNomDeclarant($coordonneesTiersRequest->getNom())
            ->setPrenomDeclarant($coordonneesTiersRequest->getPrenom())
            ->setMailDeclarant($coordonneesTiersRequest->getMail())
            ->setTelDeclarant($coordonneesTiersRequest->getTelephone())
            ->setLienDeclarantOccupant($coordonneesTiersRequest->getLien())
            ->setStructureDeclarant($coordonneesTiersRequest->getStructure());

        $informationComplementaire = new InformationComplementaire();
        if (!empty($signalement->getInformationComplementaire())) {
            $informationComplementaire = clone $signalement->getInformationComplementaire();
        }
        if ($coordonneesTiersRequest->getDateNaissance()) {
            $informationComplementaire->setInformationsComplementairesSituationBailleurDateNaissance(
                $coordonneesTiersRequest->getDateNaissance()
            );
            $signalement->setInformationComplementaire($informationComplementaire);
        }

        $this->save($signalement);

        return $this->suiviManager->addSuiviIfNeeded(
            signalement: $signalement,
            description: 'Les coordonnées du tiers déclarant ont été modifiées par ',
        );
    }

    public function updateFromCoordonneesFoyerRequest(
        Signalement $signalement,
        CoordonneesFoyerRequest $coordonneesFoyerRequest,
    ): bool {
        if (ProfileDeclarant::BAILLEUR_OCCUPANT == $signalement->getProfileDeclarant()) {
            $signalement
                ->setTypeProprio(
                    $coordonneesFoyerRequest->getTypeProprio()
                    ? ProprioType::from($coordonneesFoyerRequest->getTypeProprio())
                    : null
                )
                ->setStructureDeclarant($coordonneesFoyerRequest->getNomStructure())
                ->setDenominationProprio($coordonneesFoyerRequest->getNomStructure());
        }
        $signalement
            ->setCiviliteOccupant($coordonneesFoyerRequest->getCivilite())
            ->setNomOccupant($coordonneesFoyerRequest->getNom())
            ->setPrenomOccupant($coordonneesFoyerRequest->getPrenom())
            ->setMailOccupant($coordonneesFoyerRequest->getMail())
            ->setTelOccupant($coordonneesFoyerRequest->getTelephone())
            ->setTelOccupantBis($coordonneesFoyerRequest->getTelephoneBis());

        $this->save($signalement);

        return $this->suiviManager->addSuiviIfNeeded(
            signalement: $signalement,
            description: 'Les coordonnées du foyer ont été modifiées par ',
        );
    }

    public function updateFromCoordonneesBailleurRequest(
        Signalement $signalement,
        CoordonneesBailleurRequest $coordonneesBailleurRequest,
    ): bool {
        $bailleur = null;
        if ($signalement->getIsLogementSocial() && $coordonneesBailleurRequest->getDenomination()) {
            $bailleur = $this->bailleurRepository->findOneBailleurBy(
                $coordonneesBailleurRequest->getDenomination(),
                $this->zipcodeProvider->getTerritoryByInseeCode($signalement->getInseeOccupant())
            );
        }

        $signalement->setBailleur($bailleur)
            ->setTypeProprio(
                $coordonneesBailleurRequest->getTypeProprio()
                ? ProprioType::from($coordonneesBailleurRequest->getTypeProprio())
                : null
            )
            ->setDenominationProprio($coordonneesBailleurRequest->getDenomination())
            ->setNomProprio($coordonneesBailleurRequest->getNom())
            ->setPrenomProprio($coordonneesBailleurRequest->getPrenom())
            ->setMailProprio($coordonneesBailleurRequest->getMail())
            ->setTelProprio($coordonneesBailleurRequest->getTelephone())
            ->setTelProprioSecondaire($coordonneesBailleurRequest->getTelephoneBis())
            ->setAdresseProprio($coordonneesBailleurRequest->getAdresse())
            ->setCodePostalProprio($coordonneesBailleurRequest->getCodePostal())
            ->setVilleProprio($coordonneesBailleurRequest->getVille());

        $informationComplementaire = new InformationComplementaire();
        if (!empty($signalement->getInformationComplementaire())) {
            $informationComplementaire = clone $signalement->getInformationComplementaire();
        }
        $informationComplementaire
            ->setInformationsComplementairesSituationBailleurBeneficiaireRsa(
                $coordonneesBailleurRequest->getBeneficiaireRsa()
            )
            ->setInformationsComplementairesSituationBailleurBeneficiaireFsl(
                $coordonneesBailleurRequest->getBeneficiaireFsl()
            )
            ->setInformationsComplementairesSituationBailleurRevenuFiscal(
                $coordonneesBailleurRequest->getRevenuFiscal()
            );
        if ($coordonneesBailleurRequest->getDateNaissance()) {
            $informationComplementaire->setInformationsComplementairesSituationBailleurDateNaissance(
                $coordonneesBailleurRequest->getDateNaissance()
            );
        }
        $signalement->setInformationComplementaire($informationComplementaire);

        $this->save($signalement);

        return $this->suiviManager->addSuiviIfNeeded(
            signalement: $signalement,
            description: 'Les coordonnées du bailleur ont été modifiées par ',
        );
    }

    public function updateFromCoordonneesAgenceRequest(
        Signalement $signalement,
        CoordonneesAgenceRequest $coordonneesAgenceRequest,
    ): bool {
        $signalement
            ->setDenominationAgence($coordonneesAgenceRequest->getDenomination())
            ->setNomAgence($coordonneesAgenceRequest->getNom())
            ->setPrenomAgence($coordonneesAgenceRequest->getPrenom())
            ->setMailAgence($coordonneesAgenceRequest->getMail())
            ->setTelAgence($coordonneesAgenceRequest->getTelephone())
            ->setTelAgenceSecondaire($coordonneesAgenceRequest->getTelephoneBis())
            ->setAdresseAgence($coordonneesAgenceRequest->getAdresse())
            ->setCodePostalAgence($coordonneesAgenceRequest->getCodePostal())
            ->setVilleAgence($coordonneesAgenceRequest->getVille());

        $this->save($signalement);

        return $this->suiviManager->addSuiviIfNeeded(
            signalement: $signalement,
            description: 'Les coordonnées de l\'agence ont été modifiées par ',
        );
    }

    public function updateFromInformationsLogementRequest(
        Signalement $signalement,
        InformationsLogementRequest $informationsLogementRequest,
    ): bool {
        if (is_numeric($informationsLogementRequest->getNombrePersonnes())) {
            $signalement->setNbOccupantsLogement((int) $informationsLogementRequest->getNombrePersonnes());
        }
        if (is_numeric($informationsLogementRequest->getLoyer())) {
            $signalement->setLoyer((float) $informationsLogementRequest->getLoyer());
        }
        if (!empty($informationsLogementRequest->getDateEntree())) {
            $signalement->setDateEntree(new \DateTimeImmutable($informationsLogementRequest->getDateEntree()));
        } else {
            $signalement->setDateEntree(null);
        }

        if ('oui' === $informationsLogementRequest->getLogementVacant()) {
            $signalement->setIsLogementVacant(true);
        } elseif ('non' === $informationsLogementRequest->getLogementVacant()) {
            $signalement->setIsLogementVacant(false);
        } else {
            $signalement->setIsLogementVacant(null);
        }

        $typeCompositionLogement = new TypeCompositionLogement();
        if (!empty($signalement->getTypeCompositionLogement())) {
            $typeCompositionLogement = clone $signalement->getTypeCompositionLogement();
        }

        $typeCompositionLogement
            ->setCompositionLogementNombrePersonnes($informationsLogementRequest->getNombrePersonnes())
            ->setCompositionLogementNombreEnfants($informationsLogementRequest->getCompositionLogementNombreEnfants())
            ->setCompositionLogementEnfants($informationsLogementRequest->getCompositionLogementEnfants())
            ->setBailDpeBail($informationsLogementRequest->getBailDpeBail())
            ->setBailDpeEtatDesLieux($informationsLogementRequest->getBailDpeEtatDesLieux())
            ->setBailDpeDpe($informationsLogementRequest->getBailDpeDpe())
            ->setBailDpeClasseEnergetique($informationsLogementRequest->getBailDpeClasseEnergetique())
            ->setBailDpeDateEmmenagement($signalement->getDateEntree()?->format('Y-m-d'));
        $signalement
            ->setTypeCompositionLogement($typeCompositionLogement)
            ->setNumeroInvariant($informationsLogementRequest->getBailDpeInvariant());

        $signalementQualificationNDE = $signalement->getSignalementQualifications()->filter(function ($qualification) {
            return Qualification::NON_DECENCE_ENERGETIQUE === $qualification->getQualification();
        })->first();
        if ($signalementQualificationNDE) {
            $qualificationDetails = $signalementQualificationNDE->getDetails();
            switch ($informationsLogementRequest->getBailDpeDpe()) {
                case 'oui':
                    $qualificationDetails['DPE'] = true;
                    break;
                case 'non':
                    $qualificationDetails['DPE'] = false;
                    break;
                default:
                    $qualificationDetails['DPE'] = null;
                    break;
            }
            $signalementQualificationNDE->setDetails($qualificationDetails);
            $this->save($signalementQualificationNDE);

            $signalementQualificationNDE->setStatus(
                $this->qualificationStatusService->getNDEStatus($signalementQualificationNDE)
            );
            $this->save($signalementQualificationNDE);
        }

        $informationComplementaire = new InformationComplementaire();
        if (!empty($signalement->getInformationComplementaire())) {
            $informationComplementaire = clone $signalement->getInformationComplementaire();
        }
        $informationComplementaire
            ->setInformationsComplementairesLogementMontantLoyer($informationsLogementRequest->getLoyer())
            ->setInformationsComplementairesSituationOccupantsLoyersPayes(
                $informationsLogementRequest->getLoyersPayes()
            )
            ->setInformationsComplementairesLogementAnneeConstruction(
                $informationsLogementRequest->getAnneeConstruction()
            )
            ->setInformationsComplementairesSituationBailleurDateEffetBail(
                !empty($informationsLogementRequest->getBailleurDateEffetBail())
                ? $informationsLogementRequest->getBailleurDateEffetBail()
                : null
            );

        $signalement->setInformationComplementaire($informationComplementaire);

        $this->updateDesordresAndScoreWithSuroccupationChanges($signalement);
        $this->signalementQualificationUpdater->updateQualificationFromScore($signalement);

        $this->save($signalement);

        return $this->suiviManager->addSuiviIfNeeded(
            signalement: $signalement,
            description: 'Les informations sur le logement ont été modifiées par ',
        );
    }

    public function updateDesordresAndScoreWithSuroccupationChanges(
        Signalement $signalement,
        bool $removeSuroccupationDesordre = true,
    ): void {
        $situationFoyer = $signalement->getSituationFoyer();
        $typeCompositionLogement = $signalement->getTypeCompositionLogement();
        if ($signalement->isV2()) {
            if (
                null !== $situationFoyer
                && null !== $typeCompositionLogement
                && $this->suroccupationSpecification->isSatisfiedBy(
                    $situationFoyer,
                    $typeCompositionLogement
                )) {
                $precisionToLink = $this->desordrePrecisionRepository->findOneBy(
                    ['desordrePrecisionSlug' => $this->suroccupationSpecification->getSlug()]
                );
                if (null !== $precisionToLink) {
                    $signalement->addDesordrePrecision($precisionToLink);
                    $critereToLink = $precisionToLink->getDesordreCritere();
                    if (null !== $critereToLink) {
                        $signalement->addDesordreCritere($critereToLink);
                        $categorieToLink = $critereToLink->getDesordreCategorie();
                        if (null !== $categorieToLink) {
                            $signalement->addDesordreCategory($categorieToLink);
                        }
                    }
                }
            } elseif ($removeSuroccupationDesordre) {
                $precisionToLink = $this->desordrePrecisionRepository->findOneBy(
                    ['desordrePrecisionSlug' => 'desordres_type_composition_logement_suroccupation_allocataire']
                );
                $signalement->removeDesordrePrecision($precisionToLink);
                $precisionToLink = $this->desordrePrecisionRepository->findOneBy(
                    ['desordrePrecisionSlug' => 'desordres_type_composition_logement_suroccupation_non_allocataire']
                );
                $signalement->removeDesordrePrecision($precisionToLink);
                $critereToLink = $precisionToLink->getDesordreCritere();
                $signalement->removeDesordreCritere($critereToLink);
                $categorieToLink = $critereToLink->getDesordreCategorie();
                $signalement->removeDesordreCategory($categorieToLink);
            }
        }

        $signalement->setScore($this->criticiteCalculator->calculate($signalement));
    }

    public function updateFromCompositionLogementRequest(
        Signalement $signalement,
        CompositionLogementRequest $compositionLogementRequest,
    ): bool {
        $signalement->setNatureLogement($compositionLogementRequest->getType());
        $signalement->setSuperficie((float) $compositionLogementRequest->getSuperficie());

        $typeCompositionLogement = new TypeCompositionLogement();
        if (!empty($signalement->getTypeCompositionLogement())) {
            $typeCompositionLogement = clone $signalement->getTypeCompositionLogement();
        }

        if ('autre' === $compositionLogementRequest->getType()) {
            $typeCompositionLogement->setTypeLogementNatureAutrePrecision(
                $compositionLogementRequest->getTypeLogementNatureAutrePrecision()
            );
        } else {
            $typeCompositionLogement->setTypeLogementNatureAutrePrecision(null);
        }

        $typeCompositionLogement
            ->setTypeLogementNature($compositionLogementRequest->getType())
            ->setCompositionLogementPieceUnique($compositionLogementRequest->getTypeCompositionLogement())
            ->setCompositionLogementSuperficie($compositionLogementRequest->getSuperficie())
            ->setCompositionLogementNbPieces($compositionLogementRequest->getCompositionLogementNbPieces())
            ->setTypeLogementAppartementEtage($compositionLogementRequest->getEtage())
            ->setTypeLogementAppartementAvecFenetres($compositionLogementRequest->getAvecFenetres())
            ->setTypeLogementCommoditesPieceAVivre9m(
                $compositionLogementRequest->getTypeLogementCommoditesPieceAVivre9m()
            )
            ->setTypeLogementCommoditesCuisine($compositionLogementRequest->getTypeLogementCommoditesCuisine())
            ->setTypeLogementCommoditesCuisineCollective(
                $compositionLogementRequest->getTypeLogementCommoditesCuisineCollective()
            )
            ->setTypeLogementCommoditesSalleDeBain($compositionLogementRequest->getTypeLogementCommoditesSalleDeBain())
            ->setTypeLogementCommoditesSalleDeBainCollective(
                $compositionLogementRequest->getTypeLogementCommoditesSalleDeBainCollective()
            )
            ->setTypeLogementCommoditesWc($compositionLogementRequest->getTypeLogementCommoditesWc())
            ->setTypeLogementCommoditesWcCollective(
                $compositionLogementRequest->getTypeLogementCommoditesWcCollective()
            )
            ->setTypeLogementCommoditesWcCuisine($compositionLogementRequest->getTypeLogementCommoditesWcCuisine());

        $signalement->setTypeCompositionLogement($typeCompositionLogement);

        $this->desordreCompositionLogementLoader->load($signalement, $typeCompositionLogement);

        $informationComplementaire = new InformationComplementaire();
        if (!empty($signalement->getInformationComplementaire())) {
            $informationComplementaire = clone $signalement->getInformationComplementaire();
        }
        $informationComplementaire
            ->setInformationsComplementairesLogementNombreEtages($compositionLogementRequest->getNombreEtages());
        $signalement->setInformationComplementaire($informationComplementaire);

        $this->updateDesordresAndScoreWithSuroccupationChanges($signalement);
        $this->signalementQualificationUpdater->updateQualificationFromScore($signalement);

        $this->save($signalement);

        return $this->suiviManager->addSuiviIfNeeded(
            signalement: $signalement,
            description: 'La description du logement a été modifiée par ',
        );
    }

    /**
     * @throws \DateMalformedStringException
     */
    public function updateFromSituationFoyerRequest(
        Signalement $signalement,
        SituationFoyerRequest $situationFoyerRequest,
    ): bool {
        $signalement
            ->setIsLogementSocial(
                SignalementInputValueMapper::map(
                    $situationFoyerRequest->getIsLogementSocial()
                )
            )
            ->setIsRelogement(
                SignalementInputValueMapper::map(
                    $situationFoyerRequest->getIsRelogement()
                )
            )
            ->setIsAllocataire($situationFoyerRequest->getIsAllocataire())
            ->setNumAllocataire($situationFoyerRequest->getNumAllocataire());

        if (!empty($situationFoyerRequest->getDateNaissanceOccupant())) {
            $dateNaissance = new \DateTimeImmutable($situationFoyerRequest->getDateNaissanceOccupant());
            $signalement->setDateNaissanceOccupant($dateNaissance);
        }

        $situationFoyer = new SituationFoyer();
        if (!empty($signalement->getSituationFoyer())) {
            $situationFoyer = clone $signalement->getSituationFoyer();
        }
        $situationFoyer
            ->setLogementSocialNumeroAllocataire($situationFoyerRequest->getNumAllocataire())
            ->setLogementSocialDateNaissance($situationFoyerRequest->getDateNaissanceOccupant())
            ->setLogementSocialMontantAllocation($situationFoyerRequest->getLogementSocialMontantAllocation())
            ->setTravailleurSocialQuitteLogement($situationFoyerRequest->getTravailleurSocialQuitteLogement())
            ->setTravailleurSocialPreavisDepart($situationFoyerRequest->getTravailleurSocialPreavisDepart())
            ->setTravailleurSocialAccompagnement(
                $situationFoyerRequest->getTravailleurSocialAccompagnement()
            )
            ->setLogementSocialAllocationCaisse($situationFoyerRequest->getIsAllocataire());

        if ('non' === $situationFoyerRequest->getTravailleurSocialPreavisDepart()) {
            $signalement->setIsPreavisDepart(false);
        } elseif ('oui' === $situationFoyerRequest->getTravailleurSocialPreavisDepart()) {
            $signalement->setIsPreavisDepart(true);
        } else {
            $signalement->setIsPreavisDepart(null);
        }

        if ('non' === $situationFoyerRequest->getIsAllocataire()) {
            $situationFoyer->setLogementSocialAllocation('non');
        } elseif ('nsp' === $situationFoyerRequest->getIsAllocataire()) {
            $situationFoyer->setLogementSocialAllocation(null);
        } else {
            $situationFoyer->setLogementSocialAllocation('oui');
        }
        if (!$signalement->getIsNotOccupant()) {
            $situationFoyer
                ->setLogementSocialDemandeRelogement(
                    $situationFoyerRequest->getIsRelogement()
                );
        }
        $signalement->setSituationFoyer($situationFoyer);

        $informationComplementaire = new InformationComplementaire();
        if (!empty($signalement->getInformationComplementaire())) {
            $informationComplementaire = clone $signalement->getInformationComplementaire();
        }
        $informationComplementaire
            ->setInformationsComplementairesSituationOccupantsBeneficiaireRsa(
                $situationFoyerRequest->getBeneficiaireRsa()
            )
            ->setInformationsComplementairesSituationOccupantsBeneficiaireFsl(
                $situationFoyerRequest->getBeneficiaireFsl()
            );
        if ($signalement->getIsNotOccupant()) {
            $informationComplementaire
                ->setInformationsComplementairesSituationOccupantsDemandeRelogement(
                    $situationFoyerRequest->getIsRelogement()
                );
        }
        if ($situationFoyerRequest->getRevenuFiscal()) {
            $informationComplementaire
                ->setInformationsComplementairesSituationOccupantsRevenuFiscal(
                    $situationFoyerRequest->getRevenuFiscal()
                );
        }
        $signalement->setInformationComplementaire($informationComplementaire);

        $this->updateDesordresAndScoreWithSuroccupationChanges($signalement);
        $this->signalementQualificationUpdater->updateQualificationFromScore($signalement);
        $this->save($signalement);

        return $this->suiviManager->addSuiviIfNeeded(
            signalement: $signalement,
            description: 'La situation du foyer a été modifiée par ',
        );
    }

    public function updateFromProcedureDemarchesRequest(
        Signalement $signalement,
        ProcedureDemarchesRequest $procedureDemarchesRequest,
    ): bool {
        $signalement->setIsProprioAverti('' === $procedureDemarchesRequest->getIsProprioAverti() ? null : (bool) ($procedureDemarchesRequest->getIsProprioAverti()));

        $informationProcedure = new InformationProcedure();
        if (!empty($signalement->getInformationProcedure())) {
            $informationProcedure = clone $signalement->getInformationProcedure();
        }

        $assuranceContacteeUpdated = false;
        if ($procedureDemarchesRequest->getInfoProcedureAssuranceContactee()
        !== $informationProcedure->getInfoProcedureAssuranceContactee()) {
            $assuranceContacteeUpdated = true;
        }

        $informationProcedure
            ->setInfoProcedureBailleurPrevenu($procedureDemarchesRequest->getIsProprioAverti())
            ->setInfoProcedureBailMoyen($procedureDemarchesRequest->getInfoProcedureBailMoyen())
            ->setInfoProcedureBailDate($procedureDemarchesRequest->getInfoProcedureBailDate())
            ->setInfoProcedureBailReponse($procedureDemarchesRequest->getInfoProcedureBailReponse())
            ->setInfoProcedureBailNumero($procedureDemarchesRequest->getInfoProcedureBailNumero())
            ->setInfoProcedureAssuranceContactee($procedureDemarchesRequest->getInfoProcedureAssuranceContactee())
            ->setInfoProcedureReponseAssurance($procedureDemarchesRequest->getInfoProcedureReponseAssurance())
            ->setInfoProcedureDepartApresTravaux($procedureDemarchesRequest->getInfoProcedureDepartApresTravaux());
        $signalement->setInformationProcedure($informationProcedure);

        $informationComplementaire = new InformationComplementaire();
        if (!empty($signalement->getInformationComplementaire())) {
            $informationComplementaire = clone $signalement->getInformationComplementaire();
        }
        $signalement->setInformationComplementaire($informationComplementaire);

        if ($assuranceContacteeUpdated) {
            $this->signalementQualificationUpdater->updateQualificationFromScore($signalement);
        }

        $this->save($signalement);

        return $this->suiviManager->addSuiviIfNeeded(
            signalement: $signalement,
            description: 'Les procédures et démarches ont été modifiées par ',
        );
    }

    /**
     * @param array<string, mixed> $options
     *
     * @return array<string, mixed>|int
     */
    public function findSignalementAffectationList(User $user, array $options, bool $count = false): array|int
    {
        $maxListPagination = $options['maxItemsPerPage'] ?? SignalementAffectationListView::MAX_LIST_PAGINATION;
        $signalementAffectationList = [];

        /** @var SignalementRepository $signalementRepository */
        $signalementRepository = $this->getRepository();

        $paginator = $signalementRepository->findSignalementAffectationListPaginator($user, $options);
        $total = $paginator->count();
        if ($count) {
            return $total;
        }
        $dataResultList = $paginator->getQuery()->getResult();
        /** @var User $user */
        $user = $this->security->getUser();
        foreach ($dataResultList as $dataResultItem) {
            $signalementAffectationList[] = $this->signalementAffectationListViewFactory->createInstanceFrom(
                $user,
                $dataResultItem
            );
        }

        return [
            'pagination' => [
                'total_items' => $total,
                'current_page' => \array_key_exists('page', $options) ? (int) $options['page'] : 1,
                'total_pages' => (int) ceil($total / $maxListPagination),
                'items_per_page' => $maxListPagination,
            ],
            'list' => $signalementAffectationList,
            'filters' => $options,
        ];
    }

    /**
     * @param array<string, mixed>|null $options
     *
     * @throws Exception
     */
    public function findSignalementAffectationIterable(
        User $user,
        ?array $options = null,
    ): \Generator {
        /** @var SignalementRepository $signalementRepository */
        $signalementRepository = $this->getRepository();
        foreach ($signalementRepository->findSignalementAffectationIterable($user, $options) as $row) {
            yield $this->signalementExportFactory->createInstanceFrom(
                $user,
                $row,
            );
        }
    }

    public function activateSignalementAndCreateFirstSuivi(Signalement $signalement, ?User $adminUser): bool
    {
        $signalement->setStatut(SignalementStatus::ACTIVE);
        $signalement->setValidatedAt(new \DateTimeImmutable());
        $this->persist($signalement);
        $subscriptionCreated = false;
        $suivi = $this->suiviManager->createSuivi(
            user: $adminUser,
            signalement: $signalement,
            description: 'Signalement validé',
            type: Suivi::TYPE_AUTO,
            category: SuiviCategory::SIGNALEMENT_IS_ACTIVE,
            isPublic: true,
            context: Suivi::CONTEXT_SIGNALEMENT_ACCEPTED,
            flush: false,
            subscriptionCreated: $subscriptionCreated
        );
        $this->persist($suivi);

        return $subscriptionCreated;
    }
}
