<?php

namespace App\Service\Signalement;

use App\Dto\Request\Signalement\SignalementSearchQuery;
use App\Entity\Affectation;
use App\Entity\Enum\AffectationStatus;
use App\Entity\Enum\Qualification;
use App\Entity\Enum\QualificationStatus;
use App\Entity\Enum\SignalementStatus;
use App\Entity\Enum\SuiviCategory;
use App\Entity\Enum\VisiteStatus;
use App\Entity\Intervention;
use App\Entity\Partner;
use App\Entity\User;
use App\Repository\BailleurRepository;
use App\Repository\EpciRepository;
use App\Repository\NotificationRepository;
use App\Repository\SignalementQualificationRepository;
use App\Repository\SuiviRepository;
use App\Repository\TerritoryRepository;
use App\Service\DashboardTabPanel\TabDossier;
use App\Utils\CommuneHelper;
use App\Utils\ImportCommune;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\DBAL\Exception;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use function Symfony\Component\String\u;

class SearchFilter
{
    private SignalementSearchQuery $request;

    public function __construct(
        private NotificationRepository $notificationRepository,
        private SuiviRepository $suiviRepository,
        private TerritoryRepository $territoryRepository,
        private EntityManagerInterface $entityManager,
        private SignalementQualificationRepository $signalementQualificationRepository,
        private EpciRepository $epciRepository,
        private BailleurRepository $bailleurRepository,
        #[Autowire(env: 'FEATURE_NEW_DASHBOARD')]
        private bool $featureNewDashboard,
    ) {
    }

    public function setRequest(SignalementSearchQuery $request): static
    {
        $this->request = $request;

        return $this;
    }

    /**
     * @return array<mixed>
     */
    public function buildFilters(User $user): array
    {
        /** @var SignalementSearchQuery $signalementSearchQuery */
        $signalementSearchQuery = $this->request;
        $filters = $signalementSearchQuery->getFilters();
        $partners = new ArrayCollection();

        if (isset($filters['territories'])) {
            $authorizedTerritories = $user->getPartnersTerritories();
            foreach ($filters['territories'] as $key => $requestedTerritoryId) {
                if (!$user->isSuperAdmin() && !isset($authorizedTerritories[$requestedTerritoryId])) {
                    unset($filters['territories'][$key]);
                }
            }
        }
        $territory = isset($filters['territories'][0]) ? $this->territoryRepository->find($filters['territories'][0]) : null;
        if (\in_array(User::ROLE_USER_PARTNER, $user->getRoles())) {
            $partners = $user->getPartners();
        }

        if (isset($filters['delays'])) {
            $filters['delays_partners'] = $partners->map(fn ($partner) => $partner->getId())->toArray();
            $filters['delays_territory'] = $territory;
        }

        if (isset($filters['nouveau_suivi'])) {
            $signalementIds = $this->notificationRepository->findSignalementNewSuivi($user, $territory);
            $filters['signalement_ids'] = $signalementIds;
        }

        if (isset($filters['bailleurSocial'])) {
            $filters['bailleurSocial'] = $this->bailleurRepository->findOneBy(['id' => $filters['bailleurSocial']]);
        }

        return $filters;
    }

    /**
     * @param array<mixed> $filters
     *
     * @throws Exception
     */
    public function applyFilters(QueryBuilder $qb, array $filters, User $user): QueryBuilder
    {
        if (!empty($filters['searchterms'])) {
            if (preg_match('/([0-9]{4})-[0-9]{0,6}/', $filters['searchterms'])) {
                $qb->andWhere('s.reference = :searchterms');
                $qb->setParameter('searchterms', $filters['searchterms']);
            } elseif (preg_match('/([0-9]{5})/', $filters['searchterms'])) {
                $qb->andWhere('s.cpOccupant = :searchterms');
                $qb->setParameter('searchterms', $filters['searchterms']);
            } else {
                $qb->andWhere('LOWER(s.nomOccupant) LIKE :searchterms
                OR LOWER(s.prenomOccupant) LIKE :searchterms
                OR LOWER(s.mailOccupant) LIKE :searchterms
                OR LOWER(s.reference) LIKE :searchterms
                OR LOWER(s.adresseOccupant) LIKE :searchterms
                OR LOWER(s.villeOccupant) LIKE :searchterms
                OR LOWER(s.nomProprio) LIKE :searchterms');
                $qb->setParameter('searchterms', '%'.mb_trim(strtolower($filters['searchterms'])).'%');
            }
        }
        if (!empty($filters['affectations']) && (bool) empty($filters['partners'])) {
            $qb->andWhere('a.statut IN (:affectations)')
                ->setParameter('affectations', $filters['affectations']);
        }
        if (!empty($filters['partners'])) {
            $qb->leftJoin('s.affectations', 'afilt');
            if (\in_array('AUCUN', $filters['partners'])) {
                $qb->andWhere('afilt.partner IS NULL');
            } else {
                $qb->andWhere('afilt.partner IN (:partners)');
                if (!empty($filters['affectations'])) {
                    $qb->andWhere('afilt.statut IN (:affectations)')
                    ->setParameter('affectations', $filters['affectations']);
                }
                $qb->setParameter('partners', $filters['partners']);
            }
        }
        if (!empty($filters['closed_affectation'])) {
            $qb->having('affectationPartnerName IS NOT NULL');
            if (\in_array('ALL_OPEN', $filters['closed_affectation'])) {
                // les id de tous les signalements ayant au moins une affectation fermée :
                $subquery = $this->entityManager->getRepository(Affectation::class)->createQueryBuilder('a')
                    ->select('DISTINCT s.id')
                    ->innerJoin('a.signalement', 's')
                    ->where('a.statut = :statut_affectation_closed')
                    ->setParameter('statut_affectation_closed', AffectationStatus::CLOSED->value);

                // les signalements n'ayant aucune affectation fermée :
                $qb->andWhere('s.id NOT IN (:subquery)')
                    ->setParameter('subquery', $subquery->getQuery()->getSingleColumnResult());
            }
            if (\in_array('ONE_CLOSED', $filters['closed_affectation'])) {
                // les id de tous les signalements ayant au moins une affectation fermée :
                $subqueryClosedAffectation = $this->entityManager->getRepository(Affectation::class)
                    ->createQueryBuilder('a')
                    ->select('DISTINCT IDENTITY(a.signalement)')
                    ->innerJoin('a.signalement', 's')
                    ->where('a.statut = :statut_affectation_closed')
                    ->andWhere('s.statut != :status_archived AND s.statut != :statut_closed AND s.statut != :status_draft AND s.statut != :status_draft_archived')
                    ->setParameter('status_archived', SignalementStatus::ARCHIVED->value)
                    ->setParameter('statut_closed', SignalementStatus::CLOSED->value)
                    ->setParameter('status_draft', SignalementStatus::DRAFT->value)
                    ->setParameter('status_draft_archived', SignalementStatus::DRAFT_ARCHIVED->value)
                    ->setParameter('statut_affectation_closed', AffectationStatus::CLOSED->value);

                if (!empty($filters['territories'])) {
                    $subqueryClosedAffectation->andWhere('a.territory IN (:territories)')
                        ->setParameter('territories', $filters['territories']);
                }

                $qb->andWhere('s.id IN (:subqueryClosedAffectation)')->setParameter(
                    'subqueryClosedAffectation',
                    $subqueryClosedAffectation->getQuery()->getSingleColumnResult()
                );
            }

            if (\in_array('ALL_CLOSED', $filters['closed_affectation'])) {
                // les id de tous les signalements ayant au moins une affectation non fermée :
                $subquery = $this->entityManager->getRepository(Affectation::class)->createQueryBuilder('a')
                    ->select('DISTINCT s.id')
                    ->leftJoin('a.signalement', 's')
                    ->where('a.statut != :statut_affectation_closed')
                    ->setParameter('statut_affectation_closed', AffectationStatus::CLOSED->value);

                // les signalements n'ayant aucune affectation non fermée ou qui sont fermés
                $qb->andWhere(
                    $qb->expr()->orX(
                        $qb->expr()->notIn('s.id', ':idUnclosedAffectation'),
                        $qb->expr()->eq('s.statut', ':statut')
                    )
                )
                ->setParameter('idUnclosedAffectation', $subquery->getQuery()->getSingleColumnResult())
                ->setParameter('statut', SignalementStatus::ARCHIVED->value);
                // TODO : à vérifier
            }
        }

        if (!empty($filters['relances_usager'])) {
            if (\in_array('NO_SUIVI_AFTER_3_RELANCES', $filters['relances_usager'])) {
                $connection = $this->entityManager->getConnection();
                $parameters = [
                    'day_period' => 0,
                    'category_ask_feedback' => SuiviCategory::ASK_FEEDBACK_SENT->value,
                    'status_need_validation' => SignalementStatus::NEED_VALIDATION->value,
                    'status_archived' => SignalementStatus::ARCHIVED->value,
                    'status_closed' => SignalementStatus::CLOSED->value,
                    'status_refused' => SignalementStatus::REFUSED->value,
                    'status_draft' => SignalementStatus::DRAFT->value,
                    'status_draft_archived' => SignalementStatus::DRAFT_ARCHIVED->value,
                    'nb_suivi_technical' => 3,
                ];

                $partners = ($user->isPartnerAdmin() || $user->isUserPartner()) ? new ArrayCollection($user->getPartners()->toArray()) : new ArrayCollection();
                if (!$partners->isEmpty()) {
                    $parameters['partners'] = $partners;
                    $parameters['status_accepted'] = AffectationStatus::ACCEPTED->value;
                }
                $sql = $this->suiviRepository->getSignalementsLastAskFeedbackSuivisQuery(
                    excludeUsagerAbandonProcedure: false,
                    partners: $partners
                );

                $statement = $connection->prepare($sql);

                $qb->andWhere('s.id IN (:subQuery)')
                    ->setParameter('subQuery', $statement->executeQuery($parameters)->fetchFirstColumn());
            }
        }

        if (!empty($filters['usager_abandon_procedure'])) {
            if ($user->isSuperAdmin() || $user->isTerritoryAdmin()) {
                $qb->andWhere('s.isUsagerAbandonProcedure  = :isUsagerAbandonProcedure')
                    ->setParameter('isUsagerAbandonProcedure', $filters['usager_abandon_procedure']);
            }
        }

        if (!empty($filters['tags'])) {
            $qb->leftJoin('s.tags', 't');
            $qb->andWhere('t.id IN (:tag)')
                ->setParameter('tag', $filters['tags'])
                ->andWhere('t.isArchive = 0');
        }
        if (!empty($filters['zones'])) {
            $connection = $this->entityManager->getConnection();
            $params = $zonesParams = [];
            foreach ($filters['zones'] as $zoneId) {
                $zoneId = (int) $zoneId;
                $zonesParams[] = ':zone_'.$zoneId;
                $params['zone_'.$zoneId] = $zoneId;
            }
            $sql = '
                SELECT DISTINCT s2.id
                FROM signalement s2
                JOIN zone z ON z.id IN ('.implode(',', $zonesParams).')
                WHERE z.territory_id = s2.territory_id
                AND ST_Contains(
                    ST_GeomFromText(z.area),
                    Point(
                        JSON_EXTRACT(s2.geoloc, \'$.lng\'),
                        JSON_EXTRACT(s2.geoloc, \'$.lat\')
                    )
                ) = 1
            ';
            $stmt = $connection->prepare($sql);

            $zonesSignalements = $stmt->executeQuery($params)->fetchAllAssociative();

            if (!empty($zonesSignalements)) {
                $qb->andWhere('s.id IN (:zonesSignalements)')
                   ->setParameter('zonesSignalements', $zonesSignalements);
            } else {
                $qb->andWhere('s.id IS NULL');
            }
        }

        if (!empty($filters['statuses'])) {
            if ($user->isSuperAdmin() || $user->isTerritoryAdmin()) {
                $qb->andWhere('s.statut IN (:statuses)')
                    ->setParameter('statuses', $filters['statuses']);
            } else {
                // @todo: filter more than one status for partner
                $statuses = array_map(function ($status) {
                    return SignalementStatus::tryFrom($status)?->mapAffectationStatus();
                }, $filters['statuses']);
                $statuses = array_shift($statuses);
                $qb->having('affectationStatus LIKE :status_affectation')
                    ->setParameter('status_affectation', '%'.$statuses.'%');
            }
        }

        if (!empty($filters['cities'])) {
            foreach ($filters['cities'] as $city) {
                if (isset(CommuneHelper::COMMUNES_ARRONDISSEMENTS[$city])) {
                    $filters['cities'] = array_merge($filters['cities'], CommuneHelper::COMMUNES_ARRONDISSEMENTS[$city]);
                }
            }
            $qb->andWhere('s.villeOccupant IN (:cities) OR s.cpOccupant IN (:cities)')
                ->setParameter('cities', $filters['cities']);
        }

        if (!empty($filters['epcis'])) {
            $qb = $this->addFilterEpci($qb, $filters['epcis']);
        }

        if (!empty($filters['visites'])) {
            $qb->leftJoin('s.interventions', 'intervSearch');
            $queryVisites = '';

            foreach ($filters['visites'] as $visiteFilter) {
                $queryVisites .= ('' !== $queryVisites) ? ' OR ' : '';
                switch ($visiteFilter) {
                    case VisiteStatus::NON_PLANIFIEE->value:
                        $queryVisites .= '(intervSearch.id IS NULL)';
                        break;
                    case VisiteStatus::PLANIFIEE->value:
                        $todayDatetime = new \DateTime();
                        $queryVisites .= '(intervSearch.status = \''.Intervention::STATUS_PLANNED.'\' AND intervSearch.scheduledAt > '.$todayDatetime->format('Y-m-d').')';
                        break;
                    case VisiteStatus::CONCLUSION_A_RENSEIGNER->value:
                        $todayDatetime = new \DateTime();
                        $queryVisites .= '(intervSearch.status = \''.Intervention::STATUS_PLANNED.'\' AND intervSearch.scheduledAt <= '.$todayDatetime->format('Y-m-d').')';
                        break;
                    case VisiteStatus::TERMINEE->value:
                        $queryVisites .= '(intervSearch.status = \''.Intervention::STATUS_DONE.'\')';
                        break;
                }
            }

            $qb->andWhere($queryVisites);
        }

        if (!empty($filters['enfantsM6'])) {
            $qb = $this->addFilterEnfantM6($qb, $filters['enfantsM6']);
        }

        if (!empty($filters['avant1949'])) {
            $qb->andWhere('s.isConstructionAvant1949 IN (:avant1949)')
                ->setParameter('avant1949', $filters['avant1949']);
        }

        if (!empty($filters['criteres'])) {
            $qb->leftJoin('s.criteres', 'c');
            $qb->andWhere('c.id IN (:criteres)')
                ->setParameter('criteres', $filters['criteres']);
        }
        if (!empty($filters['housetypes'])) {
            if (\in_array('non_renseigne', $filters['housetypes'])) {
                $qb->andWhere('s.isLogementSocial IS NULL');
            } else {
                $qb->andWhere('s.isLogementSocial IN (:housetypes)')->setParameter('housetypes', $filters['housetypes']);
            }
        }
        if (!empty($filters['allocs'])) {
            if (\in_array('non_renseigne', $filters['allocs'])) {
                $qb->andWhere('s.isAllocataire IS NULL OR s.isAllocataire LIKE \'\' ');
            } else {
                $qb->andWhere('s.isAllocataire IN (:allocs)')->setParameter('allocs', $filters['allocs']);
            }
        }
        if (!empty($filters['declarants'])) {
            $qb->andWhere('s.isNotOccupant IN (:declarants)')
                ->setParameter('declarants', $filters['declarants']);
        }
        if (!empty($filters['proprios'])) {
            $qb->andWhere('s.isProprioAverti IN (:proprios)')
                ->setParameter('proprios', $filters['proprios']);
        }
        if (!empty($filters['delays'])) {
            $signalementIds = $this->suiviRepository->findSignalementNoSuiviSince(
                $filters['delays'],
                $filters['delays_territory'],
                $filters['delays_partners']
            );
            $qb->andWhere('s.id IN (:signalement_ids)')
                ->setParameter('signalement_ids', $signalementIds);
        }
        if (!empty($filters['scores'])) {
            if (!empty($filters['scores']['on'])) {
                $qb->andWhere('s.score >= :score_on')
                    ->setParameter('score_on', $filters['scores']['on']);
            }
            if (!empty($filters['scores']['off'])) {
                $qb->andWhere('s.score <= :score_off')
                    ->setParameter('score_off', $filters['scores']['off']);
            }
        }
        if (!empty($filters['territories'])) {
            $qb->andWhere('s.territory IN (:territories)')
                ->setParameter('territories', $filters['territories']);
        }

        if (!empty($filters['nde'])) {
            $subqueryResults = $this->signalementQualificationRepository->findSignalementsByQualification(
                Qualification::NON_DECENCE_ENERGETIQUE,
                $filters['nde']
            );
            $qb->andWhere('s.id IN (:subqueryResults)')
                ->setParameter('subqueryResults', $subqueryResults);
        }

        if (!empty($filters['signalement_ids'])) {
            $qb->andWhere('s.id IN (:signalement_ids)')
                ->setParameter('signalement_ids', $filters['signalement_ids']);
        }

        if (!empty($filters['typeDeclarant'])) {
            $qb
                ->andWhere('s.profileDeclarant LIKE :profile_declarant')
                ->setParameter('profile_declarant', $filters['typeDeclarant']);
        }

        if (!empty($filters['situation'])) {
            $qb = $this->addFilterSituation($qb, $filters['situation']);
        }

        if (!empty($filters['procedure'])) {
            $qb = $this->addFilterProcedure($qb, $filters['procedure']);
        }

        if (!empty($filters['procedureConstatee'])) {
            $qb->leftJoin('s.interventions', 'interventionsProcedure');
            $qb->andWhere('interventionsProcedure.concludeProcedure LIKE :procedure')
                ->setParameter('procedure', '%'.$filters['procedureConstatee'].'%');
        }

        if (!empty($filters['typeDernierSuivi'])) {
            $qb = $this->addFilterTypeDernierSuivi($qb, $filters['typeDernierSuivi']);
        }

        if (!empty($filters['dates'])) {
            $qb = $this->addFilterDate($qb, 's.createdAt', $filters['dates']);
        }

        if (!empty($filters['datesDernierSuivi'])) {
            $qb = $this->addFilterDate($qb, 's.lastSuiviAt', $filters['datesDernierSuivi']);
        }

        if (!empty($filters['statusAffectation'])) {
            $qb = $this->addFilterStatusAffectation($qb, $filters['statusAffectation']);
        }

        if (!empty($filters['isImported'])) {
            $qb = $this->addFilterImported($qb);
        } else {
            $qb->andWhere('s.isImported = false');
        }

        if (!empty($filters['motifCloture'])) {
            $qb
                ->andWhere('s.motifCloture LIKE :motif_cloture')
                ->setParameter('motif_cloture', $filters['motifCloture']);
        }

        if (!empty($filters['showMySignalementsOnly']) && $this->featureNewDashboard) {
            $qb->leftJoin('s.userSignalementSubscriptions', 'ust');
            $qb->andWhere('ust.user = :currentUser')
                ->setParameter('currentUser', $user);
        }

        if (!empty($filters['createdFrom'])) {
            if (TabDossier::CREATED_FROM_FORMULAIRE_USAGER === $filters['createdFrom']) {
                $qb->andWhere('s.createdBy IS NULL');
            } elseif (TabDossier::CREATED_FROM_FORMULAIRE_PRO === $filters['createdFrom']) {
                $qb->andWhere('s.createdBy IS NOT NULL');
            }
        }

        if (!empty($filters['isNouveauMessage']) && $this->featureNewDashboard) {
            $signalementIds = $this->suiviRepository->getSignalementsIdWithSuivisUsagersWithoutAskFeedbackBefore($user, null);
            $qb->andWhere('s.id IN (:signalement_ids)')
                ->setParameter('signalement_ids', $signalementIds);
        }

        if (!empty($filters['isMessagePostCloture']) && $this->featureNewDashboard) {
            $signalementIds = $this->suiviRepository->getSignalementsIdWithSuivisPostCloture($user, null);
            $qb->andWhere('s.id IN (:signalement_ids)')
                ->setParameter('signalement_ids', $signalementIds);
        }

        if (!empty($filters['isMessageWithoutResponse']) && $this->featureNewDashboard) {
            $signalementIds = $this->suiviRepository->getSignalementsIdWithSuivisUsagerOrPoursuiteWithAskFeedbackBefore($user, null);
            $qb->andWhere('s.id IN (:signalement_ids)')
                ->setParameter('signalement_ids', $signalementIds);
        }

        if (!empty($filters['isMessageWithoutResponse']) && $this->featureNewDashboard) {
            $signalementIds = $this->suiviRepository->getSignalementsIdWithSuivisUsagerOrPoursuiteWithAskFeedbackBefore($user, null);
            $qb->andWhere('s.id IN (:signalement_ids)')
                ->setParameter('signalement_ids', $signalementIds);
        }

        return $qb;
    }

    private function addFilterSituation(QueryBuilder $qb, string $situation): QueryBuilder
    {
        switch ($situation) {
            case 'attente_relogement':
                $qb->andWhere('s.isRelogement = :is_relogement')->setParameter('is_relogement', true);
                break;
            case 'bail_en_cours':
                $qb->andWhere('s.isBailEnCours = :is_bail_en_cours')->setParameter('is_bail_en_cours', true);
                break;
            case 'preavis_de_depart':
                $qb->andWhere('s.isPreavisDepart = :is_preavis_depart')->setParameter('is_preavis_depart', true);
                break;
        }

        return $qb;
    }

    private function addFilterProcedure(QueryBuilder $qb, string $procedure): QueryBuilder
    {
        $qualification = Qualification::tryFrom($procedure);
        if (Qualification::NON_DECENCE_ENERGETIQUE === $qualification) {
            $subqueryResults = $this->signalementQualificationRepository->findSignalementsByQualification(
                $qualification,
                [QualificationStatus::NDE_AVEREE, QualificationStatus::NDE_CHECK],
                false
            );
        } else {
            $subqueryResults = $this->signalementQualificationRepository->findSignalementsByQualification(
                $qualification,
                null,
                false
            );
        }

        $qb
            ->andWhere('s.id IN (:subqueryResults)')
            ->setParameter('subqueryResults', $subqueryResults);

        return $qb;
    }

    private function addFilterTypeDernierSuivi(QueryBuilder $qb, string $typeDernierSuivi): QueryBuilder
    {
        if ('automatique' === $typeDernierSuivi) {
            $values = [Partner::DEFAULT_PARTNER, 'MESSAGE AUTOMATIQUE'];
            $qb
                ->andWhere('s.lastSuiviBy IN (:typeDernierSuivi)')
                ->setParameter('typeDernierSuivi', $values);
        } elseif ('usager' === $typeDernierSuivi) {
            $values = ['DECLARANT', 'OCCUPANT', 'Aucun'];
            $qb
                ->andWhere('s.lastSuiviBy IN (:typeDernierSuivi)')
                ->setParameter('typeDernierSuivi', $values);
        } else {
            $values = ['DECLARANT', 'OCCUPANT', 'Aucun', Partner::DEFAULT_PARTNER, 'MESSAGE AUTOMATIQUE'];
            $qb
                ->andWhere('s.lastSuiviBy NOT IN (:typeDernierSuivi)')
                ->setParameter('typeDernierSuivi', $values);
        }

        return $qb;
    }

    private function addFilterStatusAffectation(QueryBuilder $qb, string $statusAffectation): QueryBuilder
    {
        if ('accepte' === $statusAffectation
            || 'en_attente' === $statusAffectation
            || 'refuse' === $statusAffectation
        ) {
            $status = AffectationStatus::mapFilterStatus($statusAffectation);
            $qb
                ->having('affectationStatus LIKE :status_affectation')
                ->setParameter('status_affectation', '%'.$status.'%');
        }

        return $qb;
    }

    /**
     * @param array<string> $epcis
     */
    private function addFilterEpci(QueryBuilder $qb, array $epcis): QueryBuilder
    {
        $communes = $this->epciRepository->findCommunesByEpcis($epcis);
        $orX = $qb->expr()->orX();
        foreach ($communes as $key => $commune) {
            $orX->add($qb->expr()->andX(
                $qb->expr()->eq('s.cpOccupant', ':cpOccupant_'.$key),
                $qb->expr()->eq('s.villeOccupant', ':villeOccupant_'.$key)
            ));

            $qb
                ->setParameter('cpOccupant_'.$key, $commune['codePostal'])
                ->setParameter(
                    'villeOccupant_'.$key,
                    ImportCommune::sanitizeCommuneWithArrondissement($commune['nom'])
                );
        }

        $qb->andWhere($orX);

        return $qb;
    }

    /**
     * @param array<string> $dates
     */
    private function addFilterDate(QueryBuilder $qb, string $columnDbField, array $dates): QueryBuilder
    {
        if (!empty($dates['on'])) {
            $paramName = 'date_in_'.u($columnDbField)->snake();
            $qb->andWhere($columnDbField.' >= :'.$paramName)->setParameter($paramName, $dates['on']);
        }

        if (!empty($dates['off'])) {
            $endDate = new \DateTime($dates['off']);
            $endDate->add(new \DateInterval('P1D'));
            $paramName = 'date_off_'.u($columnDbField)->snake();
            $qb->andWhere($columnDbField.' <= :'.$paramName)->setParameter($paramName, $endDate->format('Y-m-d'));
        }

        return $qb;
    }

    private function addFilterImported(QueryBuilder $qb): QueryBuilder
    {
        $qb->andWhere('(s.isImported = true OR s.isImported = false)');

        return $qb;
    }

    /**
     * @param array<string> $enfantM6
     */
    private function addFilterEnfantM6(QueryBuilder $qb, array $enfantM6): QueryBuilder
    {
        if (\in_array('non_renseigne', $enfantM6)) {
            $qb->andWhere(
                $qb->expr()->andX(
                    $qb->expr()->orX(
                        $qb->expr()->isNull('s.nbEnfantsM6'),
                        $qb->expr()->like('s.nbEnfantsM6', $qb->expr()->literal(''))
                    ),
                    $qb->expr()->orX(
                        $qb->expr()->isNull(
                            'JSON_EXTRACT(s.typeCompositionLogement, \'$.composition_logement_enfants\')'
                        ),
                        $qb->expr()->like(
                            'JSON_EXTRACT(s.typeCompositionLogement, \'$.composition_logement_enfants\')',
                            $qb->expr()->literal('')
                        )
                    )
                )
            );
        } elseif (\in_array(0, $enfantM6)) {
            $qb->andWhere(
                $qb->expr()->orX(
                    $qb->expr()->eq('s.nbEnfantsM6', 0),
                    $qb->expr()->eq(
                        'JSON_EXTRACT(s.typeCompositionLogement,
                            \'$.composition_logement_enfants\')',
                        ':non'
                    )
                )
            );
            $qb->setParameter('non', 'non');
        } elseif (\in_array(1, $enfantM6)) {
            $qb->andWhere(
                $qb->expr()->orX(
                    $qb->expr()->gt('s.nbEnfantsM6', 0),
                    $qb->expr()->eq(
                        'JSON_EXTRACT(s.typeCompositionLogement,
                            \'$.composition_logement_enfants\')',
                        ':oui'
                    )
                )
            );
            $qb->setParameter('oui', 'oui');
        }

        return $qb;
    }
}
