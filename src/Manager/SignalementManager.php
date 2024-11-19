<?php

namespace App\Manager;

use App\Dto\Request\Signalement\AdresseOccupantRequest;
use App\Dto\Request\Signalement\CompositionLogementRequest;
use App\Dto\Request\Signalement\CoordonneesBailleurRequest;
use App\Dto\Request\Signalement\CoordonneesFoyerRequest;
use App\Dto\Request\Signalement\CoordonneesTiersRequest;
use App\Dto\Request\Signalement\InformationsLogementRequest;
use App\Dto\Request\Signalement\ProcedureDemarchesRequest;
use App\Dto\Request\Signalement\QualificationNDERequest;
use App\Dto\Request\Signalement\SituationFoyerRequest;
use App\Dto\SignalementAffectationListView;
use App\Entity\Affectation;
use App\Entity\Enum\AffectationStatus;
use App\Entity\Enum\MotifCloture;
use App\Entity\Enum\ProfileDeclarant;
use App\Entity\Enum\ProprioType;
use App\Entity\Model\InformationComplementaire;
use App\Entity\Model\InformationProcedure;
use App\Entity\Model\SituationFoyer;
use App\Entity\Model\TypeCompositionLogement;
use App\Entity\Partner;
use App\Entity\Signalement;
use App\Entity\SignalementQualification;
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
use App\Service\DataGouv\AddressService;
use App\Service\DataGouv\Response\Address;
use App\Service\Signalement\CriticiteCalculator;
use App\Service\Signalement\DesordreTraitement\DesordreCompositionLogementLoader;
use App\Service\Signalement\Qualification\QualificationStatusService;
use App\Service\Signalement\Qualification\SignalementQualificationUpdater;
use App\Service\Signalement\SignalementInputValueMapper;
use App\Service\Signalement\ZipcodeProvider;
use App\Specification\Signalement\SuroccupationSpecification;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class SignalementManager extends AbstractManager
{
    public function __construct(
        protected ManagerRegistry $managerRegistry,
        private Security $security,
        private SignalementFactory $signalementFactory,
        private EventDispatcherInterface $eventDispatcher,
        private QualificationStatusService $qualificationStatusService,
        private SignalementAffectationListViewFactory $signalementAffectationListViewFactory,
        private SignalementExportFactory $signalementExportFactory,
        private ParameterBagInterface $parameterBag,
        private SuroccupationSpecification $suroccupationSpecification,
        private CriticiteCalculator $criticiteCalculator,
        private SignalementQualificationUpdater $signalementQualificationUpdater,
        private DesordrePrecisionRepository $desordrePrecisionRepository,
        private DesordreCompositionLogementLoader $desordreCompositionLogementLoader,
        private SuiviManager $suiviManager,
        private BailleurRepository $bailleurRepository,
        private AddressService $addressService,
        private AffectationRepository $affectationRepository,
        string $entityName = Signalement::class,
    ) {
        parent::__construct($managerRegistry, $entityName);
    }

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

    public function update(Signalement $signalement, array $data): Signalement
    {
        if (empty($data['statut'])) {
            $data['statut'] = Signalement::STATUS_ACTIVE;
            if ($data['motifCloture'] || $data['closedAt']) {
                $data['statut'] = Signalement::STATUS_CLOSED;
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
            ->setStatut((int) $data['statut'])
            ->setValidatedAt(
                Signalement::STATUS_ACTIVE === $data['statut'] ? $data['createdAt'] : new \DateTimeImmutable()
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
        $signalement->setGeoloc($address->getGeoloc());

        if (empty($signalement->getCpOccupant())) {
            $signalement->setCpOccupant($address->getZipCode());
        }
    }

    public function findAllPartners(Signalement $signalement): array
    {
        /** @var PartnerRepository $partnerRepository */
        $partnerRepository = $this->managerRegistry->getRepository(Partner::class);
        $partners['affected'] = $partnerRepository->findByLocalization(
            signalement: $signalement,
            affected: true
        );

        $partners['not_affected'] = $partnerRepository->findByLocalization(
            signalement: $signalement,
            affected: false
        );

        return $partners;
    }

    public function findPartners(Signalement $signalement): array
    {
        $affectation = $signalement->getAffectations()->map(
            function (Affectation $affectation) {
                return $affectation->getPartner()->getId();
            }
        );

        return $affectation->toArray();
    }

    public function closeSignalementForAllPartners(Signalement $signalement, MotifCloture $motif): Signalement
    {
        $signalement
            ->setStatut(Signalement::STATUS_CLOSED)
            ->setMotifCloture($motif)
            ->setClosedAt(new \DateTimeImmutable());

        $this->affectationRepository->closeBySignalement($signalement, $motif, $this->security->getUser());
        $this->managerRegistry->getManager()->flush();

        return $signalement;
    }

    public function findEmailsAffectedToSignalement(Signalement $signalement): array
    {
        $sendTo = [];
        /** @var SignalementRepository $signalementRepository */
        $signalementRepository = $this->getRepository();

        $usersPartnerEmail = $signalementRepository->findUsersPartnerEmailAffectedToSignalement(
            $signalement->getId(),
        );
        $sendTo = array_merge($sendTo, $usersPartnerEmail);

        $partnersEmail = $signalementRepository->findPartnersEmailAffectedToSignalement(
            $signalement->getId()
        );

        return array_merge($sendTo, $partnersEmail);
    }

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

    public function updateFromSignalementQualification(
        SignalementQualification $signalementQualification,
        QualificationNDERequest $qualificationNDERequest,
    ) {
        $signalement = $signalementQualification->getSignalement();
        // mise à jour du signalement
        if ($qualificationNDERequest->getDateEntree()) {
            if (QualificationNDERequest::RADIO_VALUE_AFTER_2023 === $qualificationNDERequest->getDateEntree()
                && (null === $signalement->getDateEntree() || $signalement->getDateEntree()->format('Y') < '2023')
            ) {
                $signalement->setDateEntree(new \DateTimeImmutable(QualificationNDERequest::RADIO_VALUE_AFTER_2023));
            }

            if (QualificationNDERequest::RADIO_VALUE_BEFORE_2023 === $qualificationNDERequest->getDateEntree()
                && (null === $signalement->getDateEntree() || $signalement->getDateEntree()->format('Y') >= '2023')
            ) {
                $signalement->setDateEntree(new \DateTimeImmutable(QualificationNDERequest::RADIO_VALUE_BEFORE_2023));
            }
        }

        if (null !== $qualificationNDERequest->getSuperficie()
            && $signalement->getSuperficie() !== $qualificationNDERequest->getSuperficie()
        ) {
            $signalement->setSuperficie($qualificationNDERequest->getSuperficie());
        }
        $this->save($signalement);

        // mise à jour du signalementqualification
        if ($qualificationNDERequest->getDateDernierBail()) {
            if (QualificationNDERequest::RADIO_VALUE_AFTER_2023 === $qualificationNDERequest->getDateDernierBail()
                && (
                    null === $signalementQualification->getDernierBailAt()
                    || $signalementQualification->getDernierBailAt()->format('Y') < '2023'
                )
            ) {
                $signalementQualification->setDernierBailAt(new \DateTimeImmutable(
                    QualificationNDERequest::RADIO_VALUE_AFTER_2023
                ));
            }
            if (QualificationNDERequest::RADIO_VALUE_BEFORE_2023 === $qualificationNDERequest->getDateDernierBail()
                && (
                    null === $signalementQualification->getDernierBailAt()
                    || $signalementQualification->getDernierBailAt()->format('Y') >= '2023'
                )
            ) {
                $signalementQualification->setDernierBailAt(
                    new \DateTimeImmutable(QualificationNDERequest::RADIO_VALUE_BEFORE_2023)
                );
            }
        }

        $signalementQualification->setDetails($qualificationNDERequest->getDetails());

        $this->save($signalementQualification);

        $signalementQualification->setStatus(
            $this->qualificationStatusService->getNDEStatus($signalementQualification)
        );

        $this->save($signalementQualification);
    }

    public function updateFromAdresseOccupantRequest(
        Signalement $signalement,
        AdresseOccupantRequest $adresseOccupantRequest,
    ) {
        $signalement->setAdresseOccupant($adresseOccupantRequest->getAdresse())
            ->setCpOccupant($adresseOccupantRequest->getCodePostal())
            ->setVilleOccupant($adresseOccupantRequest->getVille())
            ->setInseeOccupant($adresseOccupantRequest->getInsee())
            ->setGeoloc([
                'lat' => $adresseOccupantRequest->getGeolocLat(),
                'lng' => $adresseOccupantRequest->getGeolocLng(),
            ])

            ->setEtageOccupant($adresseOccupantRequest->getEtage())
            ->setEscalierOccupant($adresseOccupantRequest->getEscalier())
            ->setNumAppartOccupant($adresseOccupantRequest->getNumAppart())
            ->setAdresseAutreOccupant($adresseOccupantRequest->getAutre())
            ->setManualAddressOccupant('1' === $adresseOccupantRequest->getManual());

        if ('1' === $adresseOccupantRequest->getNeedResetInsee()) {
            $resetAddress = $this->addressService->getAddress($adresseOccupantRequest->getCodePostal().' '.$adresseOccupantRequest->getVille());
            if (!empty($resetAddress->getCity())) {
                $signalement->setVilleOccupant($resetAddress->getCity());
            }
            if (!empty($resetAddress->getInseeCode())) {
                $signalement->setInseeOccupant($resetAddress->getInseeCode());
            }
            if (!empty($resetAddress->getLatitude())) {
                $signalement->setGeoloc([
                    'lat' => $resetAddress->getLatitude(),
                    'lng' => $resetAddress->getLongitude(),
                ]);
            }
        }

        $this->save($signalement);

        $this->suiviManager->addSuiviIfNeeded(
            signalement: $signalement,
            description: 'L\'adresse du logement a été modifiée par '
        );
    }

    public function updateFromCoordonneesTiersRequest(
        Signalement $signalement,
        CoordonneesTiersRequest $coordonneesTiersRequest,
    ) {
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

        $this->save($signalement);
        $this->suiviManager->addSuiviIfNeeded(
            signalement: $signalement,
            description: 'Les coordonnées du tiers déclarant ont été modifiées par ',
        );
    }

    public function updateFromCoordonneesFoyerRequest(
        Signalement $signalement,
        CoordonneesFoyerRequest $coordonneesFoyerRequest,
    ) {
        if (ProfileDeclarant::BAILLEUR_OCCUPANT == $signalement->getProfileDeclarant()) {
            $signalement
                ->setTypeProprio(
                    $coordonneesFoyerRequest->getTypeProprio()
                    ? ProprioType::from($coordonneesFoyerRequest->getTypeProprio())
                    : null
                )
                ->setStructureDeclarant($coordonneesFoyerRequest->getNomStructure());
        }
        $signalement
            ->setCiviliteOccupant($coordonneesFoyerRequest->getCivilite())
            ->setNomOccupant($coordonneesFoyerRequest->getNom())
            ->setPrenomOccupant($coordonneesFoyerRequest->getPrenom())
            ->setMailOccupant($coordonneesFoyerRequest->getMail())
            ->setTelOccupant($coordonneesFoyerRequest->getTelephone())
            ->setTelOccupantBis($coordonneesFoyerRequest->getTelephoneBis());

        $this->save($signalement);
        $this->suiviManager->addSuiviIfNeeded(
            signalement: $signalement,
            description: 'Les coordonnées du foyer ont été modifiées par ',
        );
    }

    public function updateFromCoordonneesBailleurRequest(
        Signalement $signalement,
        CoordonneesBailleurRequest $coordonneesBailleurRequest,
    ) {
        $bailleur = null;
        if ($signalement->getIsLogementSocial() && $coordonneesBailleurRequest->getNom()) {
            $bailleur = $this->bailleurRepository->findOneBailleurBy(
                $coordonneesBailleurRequest->getNom(),
                ZipcodeProvider::getZipCode($signalement->getInseeOccupant())
            );
        }

        $signalement->setBailleur($bailleur)
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
        $this->suiviManager->addSuiviIfNeeded(
            signalement: $signalement,
            description: 'Les coordonnées du bailleur ont été modifiées par ',
        );
    }

    public function updateFromInformationsLogementRequest(
        Signalement $signalement,
        InformationsLogementRequest $informationsLogementRequest,
    ) {
        if (is_numeric($informationsLogementRequest->getNombrePersonnes())) {
            $signalement->setNbOccupantsLogement($informationsLogementRequest->getNombrePersonnes());
        }
        if (is_numeric($informationsLogementRequest->getLoyer())) {
            $signalement->setLoyer($informationsLogementRequest->getLoyer());
        }
        if (!empty($informationsLogementRequest->getDateEntree())) {
            $signalement->setDateEntree(new \DateTimeImmutable($informationsLogementRequest->getDateEntree()));
        } else {
            $signalement->setDateEntree(null);
        }

        $typeCompositionLogement = new TypeCompositionLogement();
        if (!empty($signalement->getTypeCompositionLogement())) {
            $typeCompositionLogement = clone $signalement->getTypeCompositionLogement();
        }

        $typeCompositionLogement
            ->setCompositionLogementNombrePersonnes($informationsLogementRequest->getNombrePersonnes())
            ->setCompositionLogementEnfants($informationsLogementRequest->getCompositionLogementEnfants())
            ->setBailDpeBail($informationsLogementRequest->getBailDpeBail())
            ->setBailDpeEtatDesLieux($informationsLogementRequest->getBailDpeEtatDesLieux())
            ->setBailDpeDpe($informationsLogementRequest->getBailDpeDpe())
            ->setBailDpeClasseEnergetique($informationsLogementRequest->getBailDpeClasseEnergetique());
        $signalement->setTypeCompositionLogement($typeCompositionLogement);

        $informationComplementaire = new InformationComplementaire();
        if (!empty($signalement->getInformationComplementaire())) {
            $informationComplementaire = clone $signalement->getInformationComplementaire();
        }
        $informationComplementaire
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
        $this->suiviManager->addSuiviIfNeeded(
            signalement: $signalement,
            description: 'Les informations sur le logement ont été modifiées par ',
        );
    }

    private function updateDesordresAndScoreWithSuroccupationChanges(
        Signalement $signalement,
    ) {
        $situationFoyer = $signalement->getSituationFoyer();
        $typeCompositionLogement = $signalement->getTypeCompositionLogement();
        if ($signalement->getCreatedFrom()) {
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
            } else {
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
    ) {
        $signalement->setNatureLogement($compositionLogementRequest->getType());
        $signalement->setSuperficie($compositionLogementRequest->getSuperficie());

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
            ->setCompositionLogementPieceUnique($compositionLogementRequest->getTypeCompositionLogement())
            ->setCompositionLogementSuperficie($compositionLogementRequest->getSuperficie())
            ->setCompositionLogementHauteur($compositionLogementRequest->getCompositionLogementHauteur())
            ->setCompositionLogementNbPieces($compositionLogementRequest->getCompositionLogementNbPieces())
            ->setTypeLogementRdc($compositionLogementRequest->getTypeLogementRdc())
            ->setTypeLogementDernierEtage($compositionLogementRequest->getTypeLogementDernierEtage())
            ->setTypeLogementSousCombleSansFenetre($compositionLogementRequest->getTypeLogementSousCombleSansFenetre())
            ->setTypeLogementSousSolSansFenetre($compositionLogementRequest->getTypeLogementSousSolSansFenetre())
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
        $this->suiviManager->addSuiviIfNeeded(
            signalement: $signalement,
            description: 'La description du logement a été modifée par ',
        );
    }

    public function updateFromSituationFoyerRequest(
        Signalement $signalement,
        SituationFoyerRequest $situationFoyerRequest,
    ) {
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
            ->setLogementSocialMontantAllocation($situationFoyerRequest->getLogementSocialMontantAllocation())
            ->setTravailleurSocialQuitteLogement($situationFoyerRequest->getTravailleurSocialQuitteLogement())
            ->setTravailleurSocialPreavisDepart($situationFoyerRequest->getTravailleurSocialPreavisDepart())
            ->setTravailleurSocialAccompagnement(
                $situationFoyerRequest->getTravailleurSocialAccompagnement()
            )
            ->setLogementSocialAllocationCaisse($situationFoyerRequest->getIsAllocataire());

        if ('non' === $situationFoyerRequest->getTravailleurSocialPreavisDepart()) {
            $signalement->setIsPreavisDepart(false);
        } elseif ('oui' === $situationFoyerRequest->getIsAllocataire()) {
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
        $this->suiviManager->addSuiviIfNeeded(
            signalement: $signalement,
            description: 'La situation du foyer a été modifiée par ',
        );
    }

    public function updateFromProcedureDemarchesRequest(
        Signalement $signalement,
        ProcedureDemarchesRequest $procedureDemarchesRequest,
    ) {
        $signalement->setIsProprioAverti('' === $procedureDemarchesRequest->getIsProprioAverti() ? null : $procedureDemarchesRequest->getIsProprioAverti());

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
        $this->suiviManager->addSuiviIfNeeded(
            signalement: $signalement,
            description: 'Les procédures et démarches ont été modifiées par ',
        );
    }

    public function findSignalementAffectationList(User|UserInterface $user, array $options, bool $count = false): array|int
    {
        $maxListPagination = $options['maxItemsPerPage'] ?? SignalementAffectationListView::MAX_LIST_PAGINATION;
        $options['authorized_codes_insee'] = $this->parameterBag->get('authorized_codes_insee');
        $signalementAffectationList = [];

        /** @var SignalementRepository $signalementRepository */
        $signalementRepository = $this->getRepository();

        /* @var Paginator $paginator */
        $paginator = $signalementRepository->findSignalementAffectationListPaginator($user, $options);
        $total = $paginator->count();
        if ($count) {
            return $total;
        }
        $dataResultList = $paginator->getQuery()->getResult();

        foreach ($dataResultList as $dataResultItem) {
            $signalementAffectationList[] = $this->signalementAffectationListViewFactory->createInstanceFrom(
                $this->security->getUser(),
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

    public function findSignalementAffectationIterable(
        User|UserInterface $user,
        ?array $options = null,
    ): \Generator {
        $options['authorized_codes_insee'] = $this->parameterBag->get('authorized_codes_insee');

        /** @var SignalementRepository $signalementRepository */
        $signalementRepository = $this->getRepository();
        foreach ($signalementRepository->findSignalementAffectationIterable($user, $options) as $row) {
            yield $this->signalementExportFactory->createInstanceFrom(
                $user,
                $row,
            );
        }
    }
}
