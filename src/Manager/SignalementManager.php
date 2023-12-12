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
use App\Entity\Enum\MotifCloture;
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
use App\Repository\PartnerRepository;
use App\Repository\SignalementRepository;
use App\Service\DataGouv\Response\Address;
use App\Service\Signalement\QualificationStatusService;
use App\Service\Signalement\SignalementInputValueMapper;
use DateTimeImmutable;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

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
        private CsrfTokenManagerInterface $csrfTokenManager,
        private SignalementInputValueMapper $signalementInputValueMapper,
        string $entityName = Signalement::class
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
            ->setTypeLogement($data['typeLogement'])
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
            ->setModeContactProprio($data['modeContactProprio'])
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

    public function findAllPartners(Signalement $signalement, bool $addCompetences = false): array
    {
        /** @var PartnerRepository $partnerRepository */
        $partnerRepository = $this->managerRegistry->getRepository(Partner::class);
        $partners['affected'] = $partnerRepository->findByLocalization(
            signalement: $signalement,
            affected: true,
            addCompetences: $addCompetences
        );

        $partners['not_affected'] = $partnerRepository->findByLocalization(
            signalement: $signalement,
            affected: false,
            addCompetences: $addCompetences
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

        foreach ($signalement->getAffectations() as $affectation) {
            $affectation
                ->setStatut(Affectation::STATUS_CLOSED)
                ->setMotifCloture($motif)
                ->setAnsweredBy($this->security->getUser());
            $this->managerRegistry->getManager()->persist($affectation);
        }
        $this->managerRegistry->getManager()->flush();
        $this->save($signalement);

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

    public function updateFromSignalementQualification(
        SignalementQualification $signalementQualification,
        QualificationNDERequest $qualificationNDERequest
    ) {
        $signalement = $signalementQualification->getSignalement();
        // // mise à jour du signalement
        if ('2023-01-02' === $qualificationNDERequest->getDateEntree()
        && $signalement->getDateEntree()->format('Y') < '2023'
        ) {
            $signalement->setDateEntree(new DateTimeImmutable('2023-01-02'));
        }

        if ('1970-01-01' === $qualificationNDERequest->getDateEntree()
        && $signalement->getDateEntree()->format('Y') >= '2023'
        ) {
            $signalement->setDateEntree(new DateTimeImmutable('1970-01-01'));
        }

        if (null !== $qualificationNDERequest->getSuperficie()
        && $signalement->getSuperficie() !== $qualificationNDERequest->getSuperficie()
        ) {
            $signalement->setSuperficie($qualificationNDERequest->getSuperficie());
        }
        $this->save($signalement);

        // // mise à jour du signalementqualification
        if ('2023-01-02' === $qualificationNDERequest->getDateDernierBail()
        && (null === $signalementQualification->getDernierBailAt() || $signalementQualification->getDernierBailAt()?->format('Y') < '2023')
        ) {
            $signalementQualification->setDernierBailAt(new DateTimeImmutable('2023-01-02'));
        }
        if ('1970-01-01' === $qualificationNDERequest->getDateDernierBail()
        && (null === $signalementQualification->getDernierBailAt() || $signalementQualification->getDernierBailAt()?->format('Y') >= '2023')
        ) {
            $signalementQualification->setDernierBailAt(new DateTimeImmutable('1970-01-01'));
        }

        $signalementQualification->setDetails($qualificationNDERequest->getDetails());

        $this->save($signalementQualification);

        $signalementQualification->setStatus($this->qualificationStatusService->getNDEStatus($signalementQualification));

        $this->save($signalementQualification);
    }

    public function updateFromAdresseOccupantRequest(Signalement $signalement, AdresseOccupantRequest $adresseOccupantRequest)
    {
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
            ->setAdresseAutreOccupant($adresseOccupantRequest->getAutre());

        $this->save($signalement);
    }

    public function updateFromCoordonneesTiersRequest(Signalement $signalement, CoordonneesTiersRequest $coordonneesTiersRequest)
    {
        $signalement->setNomDeclarant($coordonneesTiersRequest->getNom())
            ->setPrenomDeclarant($coordonneesTiersRequest->getPrenom())
            ->setMailDeclarant($coordonneesTiersRequest->getMail())
            ->setTelDeclarant($coordonneesTiersRequest->getTelephone())
            ->setLienDeclarantOccupant($coordonneesTiersRequest->getLien())
            ->setStructureDeclarant($coordonneesTiersRequest->getStructure());

        $this->save($signalement);
    }

    public function updateFromCoordonneesFoyerRequest(Signalement $signalement, CoordonneesFoyerRequest $coordonneesFoyerRequest)
    {
        $signalement->setNomOccupant($coordonneesFoyerRequest->getNom())
            ->setPrenomOccupant($coordonneesFoyerRequest->getPrenom())
            ->setMailOccupant($coordonneesFoyerRequest->getMail())
            ->setTelOccupant($coordonneesFoyerRequest->getTelephone())
            ->setTelOccupantBis($coordonneesFoyerRequest->getTelephoneBis());

        $this->save($signalement);
    }

    public function updateFromCoordonneesBailleurRequest(Signalement $signalement, CoordonneesBailleurRequest $coordonneesBailleurRequest)
    {
        $signalement->setNomProprio($coordonneesBailleurRequest->getNom())
            ->setPrenomProprio($coordonneesBailleurRequest->getPrenom())
            ->setMailProprio($coordonneesBailleurRequest->getMail())
            ->setTelProprio($coordonneesBailleurRequest->getTelephone())
            ->setTelProprioSecondaire($coordonneesBailleurRequest->getTelephoneBis())
            ->setAdresseProprio($coordonneesBailleurRequest->getAdresse())
            ->setCodePostalProprio($coordonneesBailleurRequest->getCodePostal())
            ->setVilleProprio($coordonneesBailleurRequest->getVille());

        $this->save($signalement);
    }

    public function updateFromInformationsLogementRequest(Signalement $signalement, InformationsLogementRequest $informationsLogementRequest)
    {
        $signalement->setNatureLogement($informationsLogementRequest->getType());
        if (is_numeric($informationsLogementRequest->getNombrePersonnes())) {
            $signalement->setNbOccupantsLogement($informationsLogementRequest->getNombrePersonnes());
        }

        $typeCompositionLogement = new TypeCompositionLogement();
        if (!empty($signalement->getTypeCompositionLogement())) {
            $typeCompositionLogement = clone $signalement->getTypeCompositionLogement();
        }
        $typeCompositionLogement
            ->setCompositionLogementNombrePersonnes($informationsLogementRequest->getNombrePersonnes())
            ->setCompositionLogementEnfants($informationsLogementRequest->getCompositionLogementEnfants())
            ->setBailDpeDateEmmenagement($informationsLogementRequest->getBailDpeDateEmmenagement())
            ->setBailDpeBail($informationsLogementRequest->getBailDpeBail())
            ->setBailDpeEtatDesLieux($informationsLogementRequest->getBailDpeEtatDesLieux())
            ->setBailDpeDpe($informationsLogementRequest->getBailDpeDpe());
        $signalement->setTypeCompositionLogement($typeCompositionLogement);

        $this->save($signalement);
    }

    public function updateFromCompositionLogementRequest(Signalement $signalement, CompositionLogementRequest $compositionLogementRequest)
    {
        $signalement->setSuperficie($compositionLogementRequest->getSuperficie());

        $typeCompositionLogement = new TypeCompositionLogement();
        if (!empty($signalement->getTypeCompositionLogement())) {
            $typeCompositionLogement = clone $signalement->getTypeCompositionLogement();
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
            ->setTypeLogementCommoditesPieceAVivre9m($compositionLogementRequest->getTypeLogementCommoditesPieceAVivre9m())
            ->setTypeLogementCommoditesCuisine($compositionLogementRequest->getTypeLogementCommoditesCuisine())
            ->setTypeLogementCommoditesCuisineCollective($compositionLogementRequest->getTypeLogementCommoditesCuisineCollective())
            ->setTypeLogementCommoditesSalleDeBain($compositionLogementRequest->getTypeLogementCommoditesSalleDeBain())
            ->setTypeLogementCommoditesSalleDeBainCollective($compositionLogementRequest->getTypeLogementCommoditesSalleDeBainCollective())
            ->setTypeLogementCommoditesWc($compositionLogementRequest->getTypeLogementCommoditesWc())
            ->setTypeLogementCommoditesWcCollective($compositionLogementRequest->getTypeLogementCommoditesWcCollective())
            ->setTypeLogementCommoditesWcCuisine($compositionLogementRequest->getTypeLogementCommoditesWcCuisine());
        $signalement->setTypeCompositionLogement($typeCompositionLogement);

        // TODO : mise à jour des désordres liés à la composition du logement

        $this->save($signalement);
    }

    public function updateFromSituationFoyerRequest(Signalement $signalement, SituationFoyerRequest $situationFoyerRequest)
    {
        $signalement->setIsLogementSocial(
            $this->signalementInputValueMapper->map(
                $situationFoyerRequest->getIsLogementSocial()
            )
        );
        $signalement->setIsRelogement(
            $this->signalementInputValueMapper->map(
                $situationFoyerRequest->getIsRelogement()
            )
        );
        $signalement->setIsAllocataire(
            $this->signalementInputValueMapper->map(
                $situationFoyerRequest->getIsAllocataire()
            )
        );

        if (!empty($situationFoyerRequest->getDateNaissanceOccupant())) {
            $dateNaissance = new \DateTimeImmutable($situationFoyerRequest->getDateNaissanceOccupant());
            $signalement->setDateNaissanceOccupant($dateNaissance);
        }

        $signalement->setNumAllocataire($situationFoyerRequest->getNumAllocataire());

        $situationFoyer = new SituationFoyer();
        if (!empty($signalement->getSituationFoyer())) {
            $situationFoyer = clone $signalement->getSituationFoyer();
        }
        $situationFoyer
            ->setLogementSocialMontantAllocation($situationFoyerRequest->getLogementSocialMontantAllocation())
            ->setTravailleurSocialQuitteLogement($situationFoyerRequest->getTravailleurSocialQuitteLogement())
            ->setTravailleurSocialAccompagnementDeclarant($situationFoyerRequest->getTravailleurSocialAccompagnementDeclarant());
        $signalement->setSituationFoyer($situationFoyer);

        $this->save($signalement);
    }

    public function updateFromProcedureDemarchesRequest(Signalement $signalement, ProcedureDemarchesRequest $procedureDemarchesRequest)
    {
        $signalement->setIsProprioAverti(
            $this->signalementInputValueMapper->map(
                $procedureDemarchesRequest->getIsProprioAverti()
            )
        );

        $informationProcedure = new InformationProcedure();
        if (!empty($signalement->getInformationProcedure())) {
            $informationProcedure = clone $signalement->getInformationProcedure();
        }
        $informationProcedure
            ->setInfoProcedureAssuranceContactee($procedureDemarchesRequest->getInfoProcedureAssuranceContactee())
            ->setInfoProcedureReponseAssurance($procedureDemarchesRequest->getInfoProcedureReponseAssurance())
            ->setInfoProcedureDepartApresTravaux($procedureDemarchesRequest->getInfoProcedureDepartApresTravaux());
        $signalement->setInformationProcedure($informationProcedure);

        $this->save($signalement);
    }

    public function findSignalementAffectationList(User|UserInterface|null $user, array $options): array
    {
        $options['authorized_codes_insee'] = $this->parameterBag->get('authorized_codes_insee');
        $signalementAffectationList = [];

        /** @var SignalementRepository $signalementRepository */
        $signalementRepository = $this->getRepository();

        /** @var Paginator $paginator */
        $paginator = $signalementRepository->findSignalementAffectationListPaginator($user, $options);
        $total = $paginator->count();
        $dataResultList = $paginator->getQuery()->getResult();

        foreach ($dataResultList as $dataResultItem) {
            $signalementAffectationList[] = $this->signalementAffectationListViewFactory->createInstanceFrom(
                $this->security->getUser(),
                $dataResultItem
            );
        }

        return [
            'list' => $signalementAffectationList,
            'total' => $total,
            'page' => (int) $options['page'],
            'pages' => (int) ceil($total / SignalementAffectationListView::MAX_LIST_PAGINATION),
            'csrfTokens' => $this->generateCsrfToken($signalementAffectationList),
        ];
    }

    public function findSignalementAffectationIterable(User|UserInterface|null $user, ?array $options = null): \Generator
    {
        $options['authorized_codes_insee'] = $this->parameterBag->get('authorized_codes_insee');

        /** @var SignalementRepository $signalementRepository */
        $signalementRepository = $this->getRepository();
        foreach ($signalementRepository->findSignalementAffectationIterable($user, $options) as $row) {
            yield $this->signalementExportFactory->createInstanceFrom(
                $user,
                $row
            );
        }
    }

    /**
     * @todo: Investigate Twig\Error\RuntimeError in ajax request
     * Hack: generate csrf token in side server for ajav request
     * Fix Twig\Error\RuntimeError in order to do not generate csrf token (session) after the headers have been sent.
     */
    private function generateCsrfToken(array $signalementList): array
    {
        $csrfTokens = [];
        /* @var SignalementAffectationListView $signalement */
        foreach ($signalementList as $signalementItem) {
            if ($signalementItem instanceof SignalementAffectationListView) {
                $csrfTokens[$signalementItem->getUuid()] =
                    $this->csrfTokenManager->getToken(
                        'signalement_delete_'.$signalementItem->getId()
                    )->getValue();
            }
        }

        return $csrfTokens;
    }
}
