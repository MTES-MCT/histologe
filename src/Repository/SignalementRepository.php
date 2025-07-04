<?php

namespace App\Repository;

use App\Dto\Api\Request\SignalementListQueryParams;
use App\Dto\CountSignalement;
use App\Dto\SignalementAffectationListView;
use App\Dto\SignalementExport;
use App\Dto\StatisticsFilters;
use App\Entity\Affectation;
use App\Entity\Commune;
use App\Entity\Enum\DesordreCritereZone;
use App\Entity\Enum\Qualification;
use App\Entity\Enum\QualificationStatus;
use App\Entity\Enum\SignalementStatus;
use App\Entity\JobEvent;
use App\Entity\Partner;
use App\Entity\Signalement;
use App\Entity\Suivi;
use App\Entity\Territory;
use App\Entity\User;
use App\Entity\View\ViewLatestIntervention;
use App\Service\Interconnection\Idoss\IdossService;
use App\Service\ListFilters\SearchArchivedSignalement;
use App\Service\ListFilters\SearchDraft;
use App\Service\Signalement\SearchFilter;
use App\Service\Signalement\ZipcodeProvider;
use App\Service\Statistics\CriticitePercentStatisticProvider;
use App\Utils\CommuneHelper;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\Query\QueryException;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\ORM\TransactionRequiredException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Signalement|null find($id, $lockMode = null, $lockVersion = null)
 * @method Signalement|null findOneBy(array $criteria, array $orderBy = null)
 * @method Signalement[]    findAll()
 * @method Signalement[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SignalementRepository extends ServiceEntityRepository
{
    public const MARKERS_PAGE_SIZE = 9000; // @todo: is high cause duplicate result, the query findAllWithGeoData should be reviewed
    private const DATE_FEEDBACK_USAGER_ONLINE = '2023-03-28';

    public function __construct(
        ManagerRegistry $registry,
        private readonly SearchFilter $searchFilter,
    ) {
        parent::__construct($registry, Signalement::class);
    }

    public function save(Signalement $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * @param array<string, mixed> $options
     *
     * @return array<int, array<string, mixed>>
     */
    public function findAllWithGeoData(User $user, array $options, int $offset): array
    {
        $firstResult = $offset;

        $qb = $this->findSignalementAffectationQueryBuilder($user, $options);

        $qb->addSelect('s.geoloc, s.details, s.cpOccupant, s.inseeOccupant')
            ->andWhere("JSON_EXTRACT(s.geoloc,'$.lat') != ''")
            ->andWhere("JSON_EXTRACT(s.geoloc,'$.lng') != ''")
            ->andWhere('s.statut NOT IN (:signalement_status_list)')
            ->setParameter('signalement_status_list', [SignalementStatus::ARCHIVED, SignalementStatus::DRAFT, SignalementStatus::DRAFT_ARCHIVED]);

        $qb->setFirstResult($firstResult)->setMaxResults(self::MARKERS_PAGE_SIZE);

        return $qb->getQuery()->getArrayResult();
    }

    public function countAll(
        ?Territory $territory,
        ?ArrayCollection $partners,
        bool $removeImported = false,
        bool $removeArchived = false,
        bool $removeDraft = true,
    ): int {
        $qb = $this->createQueryBuilder('s');
        $qb->select('COUNT(s.id)');

        if ($removeArchived) {
            $qb->andWhere('s.statut != :statutArchived')
                ->setParameter('statutArchived', SignalementStatus::ARCHIVED);
        }

        if ($removeDraft) {
            $qb->andWhere('s.statut NOT IN (:statutDraft)')
                ->setParameter('statutDraft', [SignalementStatus::DRAFT, SignalementStatus::DRAFT_ARCHIVED]);
        }

        if ($removeImported) {
            $qb->andWhere('s.isImported IS NULL OR s.isImported = 0');
        }
        if ($territory) {
            $qb->andWhere('s.territory = :territory')->setParameter('territory', $territory);
        }
        if ($partners && $partners->count() > 0) {
            $qb->leftJoin('s.affectations', 'affectations')
                ->leftJoin('affectations.partner', 'partner')
                ->andWhere('partner IN (:partners)')
                ->setParameter('partners', $partners);
        }

        return $qb->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @throws NonUniqueResultException
     * @throws NoResultException
     */
    public function countImported(?Territory $territory = null, ?User $user = null): int
    {
        $qb = $this->createQueryBuilder('s')
            ->select('COUNT(s.id)')
            ->andWhere('s.statut NOT IN (:statutList)')
            ->setParameter('statutList', [SignalementStatus::ARCHIVED, SignalementStatus::DRAFT, SignalementStatus::DRAFT_ARCHIVED])
            ->andWhere('s.isImported = 1');

        if (null !== $territory) {
            $qb->andWhere('s.territory = :territory')->setParameter('territory', $territory);
        }

        if ($user && !$user->isSuperAdmin()) {
            $qb->innerJoin('s.affectations', 'affectations')
                ->innerJoin('affectations.partner', 'partner')
                ->andWhere('partner IN (:partners)')
                ->setParameter('partners', $user->getPartners());
        }

        return $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * @param array<int, Territory>                $territories
     * @param ArrayCollection<int, Partner>        $partners
     * @param array<int, QualificationStatus>|null $qualificationStatuses
     *
     * @return array<int, array<string, mixed>>
     */
    public function countByStatus(array $territories, ?ArrayCollection $partners, ?int $year = null, bool $removeImported = false, ?Qualification $qualification = null, ?array $qualificationStatuses = null): array
    {
        $qb = $this->createQueryBuilder('s');
        $qb->select('COUNT(s.id) as count')
            ->addSelect('s.statut')
            ->andWhere('s.statut NOT IN (:statutList)')
            ->setParameter('statutList', [SignalementStatus::DRAFT, SignalementStatus::DRAFT_ARCHIVED]);

        if ($removeImported) {
            $qb->andWhere('s.isImported IS NULL OR s.isImported = 0');
        }

        if (\count($territories)) {
            $qb->andWhere('s.territory IN (:territories)')->setParameter('territories', $territories);
        }
        if ($partners && $partners->count() > 0) {
            $qb->leftJoin('s.affectations', 'affectations')
                ->leftJoin('affectations.partner', 'partner')
                ->andWhere('partner IN (:partners)')
                ->setParameter('partners', $partners);
        }
        if ($year) {
            $qb->andWhere('YEAR(s.createdAt) = :year')->setParameter('year', $year);
        }

        if ($qualification) {
            $qb->innerJoin('s.signalementQualifications', 'sq')
                ->andWhere('sq.qualification = :qualification')
                ->setParameter('qualification', $qualification);

            if (!empty($qualificationStatuses)) {
                $qb->andWhere('sq.status IN (:statuses)')
                    ->setParameter('statuses', $qualificationStatuses);
            }
        }

        $qb->indexBy('s', 's.statut')
            ->groupBy('s.statut');

        return $qb->getQuery()
            ->getResult();
    }

    public function countValidated(bool $removeImported = false): int
    {
        $notStatus = [SignalementStatus::NEED_VALIDATION, SignalementStatus::ARCHIVED, SignalementStatus::DRAFT, SignalementStatus::DRAFT_ARCHIVED];
        $qb = $this->createQueryBuilder('s');
        $qb->select('COUNT(s.id)');
        $qb->andWhere('s.statut NOT IN (:notStatus)')
            ->setParameter('notStatus', $notStatus);

        if ($removeImported) {
            $qb->andWhere('s.isImported IS NULL OR s.isImported = 0');
        }

        return $qb->getQuery()
            ->getSingleScalarResult();
    }

    public function countClosed(bool $removeImported = false): int
    {
        $qb = $this->createQueryBuilder('s');
        $qb->select('COUNT(s.id)');
        $qb->andWhere('s.statut = :closedStatus')
            ->setParameter('closedStatus', SignalementStatus::CLOSED);

        if ($removeImported) {
            $qb->andWhere('s.isImported IS NULL OR s.isImported = 0');
        }

        return $qb->getQuery()
            ->getSingleScalarResult();
    }

    public function countRefused(): int
    {
        $qb = $this->createQueryBuilder('s');
        $qb->select('COUNT(s.id)');
        $qb->andWhere('s.statut = :refusedStatus')
            ->setParameter('refusedStatus', SignalementStatus::REFUSED);

        $qb->andWhere('s.isImported IS NULL OR s.isImported = 0');

        return $qb->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function countByTerritory(bool $removeImported = false): array
    {
        $qb = $this->createQueryBuilder('s');
        $qb->select('COUNT(s.id) AS count, t.zip, t.name, t.id')
            ->leftJoin('s.territory', 't')

            ->where('s.statut NOT IN (:statutList)')
            ->setParameter('statutList', [SignalementStatus::ARCHIVED, SignalementStatus::DRAFT, SignalementStatus::DRAFT_ARCHIVED]);

        if ($removeImported) {
            $qb->andWhere('s.isImported IS NULL OR s.isImported = 0');
        }

        $qb->groupBy('t.id');

        return $qb->getQuery()
            ->getResult();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function countByMonth(?Territory $territory, ?int $year, bool $removeImported = false): array
    {
        $qb = $this->createQueryBuilder('s');
        $qb->select('COUNT(s.id) AS count, MONTH(s.createdAt) AS month, YEAR(s.createdAt) AS year')

        ->where('s.statut NOT IN (:statutList)')
        ->setParameter('statutList', [SignalementStatus::ARCHIVED, SignalementStatus::DRAFT, SignalementStatus::DRAFT_ARCHIVED]);

        if ($removeImported) {
            $qb->andWhere('s.isImported IS NULL OR s.isImported = 0');
        }
        if ($territory && ZipcodeProvider::RHONE_CODE_DEPARTMENT_69 === $territory->getZip()) {
            $qb->innerJoin('s.territory', 't')
                ->andWhere('t.zip IN (:zipcodes)')
                ->setParameter('zipcodes', [ZipcodeProvider::RHONE_CODE_DEPARTMENT_69, ZipcodeProvider::METROPOLE_LYON_CODE_DEPARTMENT_69A]);
        } elseif ($territory) {
            $qb->andWhere('s.territory = :territory')->setParameter('territory', $territory);
        }
        if ($year) {
            $qb->andWhere('YEAR(s.createdAt) = :year')->setParameter('year', $year);
        }

        $qb->groupBy('month')
            ->addGroupBy('year');

        $qb->orderBy('year')
            ->addOrderBy('month');

        return $qb->getQuery()
            ->getResult();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function countBySituation(?Territory $territory, ?int $year, bool $removeImported = false): array
    {
        $qb = $this->createQueryBuilder('s');
        $qb->select('COUNT(s.id) AS count, sit.id, sit.menuLabel')
            ->leftJoin('s.situations', 'sit')

            ->where('s.statut NOT IN (:statutList)')
            ->setParameter('statutList', [SignalementStatus::ARCHIVED, SignalementStatus::DRAFT, SignalementStatus::DRAFT_ARCHIVED]);

        if ($removeImported) {
            $qb->andWhere('s.isImported IS NULL OR s.isImported = 0');
        }

        $qb->andWhere('sit.isActive = :isActive')->setParameter('isActive', true);

        if ($territory) {
            $qb->andWhere('s.territory = :territory')->setParameter('territory', $territory);
        }
        if ($year) {
            $qb->andWhere('YEAR(s.createdAt) = :year')->setParameter('year', $year);
        }

        $qb->groupBy('sit.id');

        return $qb->getQuery()
            ->getResult();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function countCritereByZone(?Territory $territory, ?int $year): array
    {
        $qb = $this->createQueryBuilder('s');

        $qb->select('SUM(CASE WHEN c.type = :batiment THEN 1 ELSE 0 END) AS critere_batiment_count')
           ->addSelect('SUM(CASE WHEN c.type = :logement THEN 1 ELSE 0 END) AS critere_logement_count')
           ->addSelect('SUM(CASE WHEN dc.zoneCategorie = :batimentString THEN 1 ELSE 0 END) AS desordrecritere_batiment_count')
           ->addSelect('SUM(CASE WHEN dc.zoneCategorie = :logementString THEN 1 ELSE 0 END) AS desordrecritere_logement_count')
           ->leftJoin('s.criteres', 'c')
           ->leftJoin('s.desordreCriteres', 'dc')
           ->setParameter('batiment', 1)
           ->setParameter('logement', 2)
           ->setParameter('batimentString', 'BATIMENT')
           ->setParameter('logementString', 'LOGEMENT');

        $qb->andWhere('s.isImported IS NULL OR s.isImported = 0');

        if ($territory) {
            $qb->andWhere('s.territory = :territory')->setParameter('territory', $territory);
        }
        if ($year) {
            $qb->andWhere('YEAR(s.createdAt) = :year')->setParameter('year', $year);
        }

        return $qb->getQuery()->getSingleResult();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function countByDesordresCriteres(
        ?Territory $territory,
        ?int $year,
        ?DesordreCritereZone $desordreCritereZone = null,
    ): array {
        $qb = $this->createQueryBuilder('s');
        $qb->select('COUNT(s.id) AS count, desordreCriteres.labelCritere')
            ->leftJoin('s.desordreCriteres', 'desordreCriteres')
            ->where('s.statut NOT IN (:statutList)')
            ->setParameter('statutList', [SignalementStatus::ARCHIVED, SignalementStatus::DRAFT, SignalementStatus::DRAFT_ARCHIVED])
            ->andWhere('s.createdFrom IS NOT NULL OR s.createdBy IS NOT NULL');

        $qb->andWhere('s.isImported IS NULL OR s.isImported = 0');

        if ($territory) {
            $qb->andWhere('s.territory = :territory')->setParameter('territory', $territory);
        }
        if ($year) {
            $qb->andWhere('YEAR(s.createdAt) = :year')->setParameter('year', $year);
        }

        if ($desordreCritereZone) {
            $qb->andWhere('desordreCriteres.zoneCategorie = :desordreCritereZone')
                ->setParameter('desordreCritereZone', $desordreCritereZone);
        }

        $qb->groupBy('desordreCriteres.labelCritere')
            ->orderBy('count', 'DESC')
            ->setMaxResults(5);

        return $qb->getQuery()
            ->getResult();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function countByMotifCloture(?Territory $territory, ?int $year, bool $removeImported = false): array
    {
        $qb = $this->createQueryBuilder('s');
        $qb->select('COUNT(s.id) AS count, s.motifCloture')

            ->where('s.motifCloture IS NOT NULL')
            ->andWhere('s.motifCloture != \'0\'')
            ->andWhere('s.closedAt IS NOT NULL')
            ->andWhere('s.statut = :statut')
            ->setParameter('statut', SignalementStatus::CLOSED);

        if ($removeImported) {
            $qb->andWhere('s.isImported IS NULL OR s.isImported = 0');
        }

        if ($territory) {
            $qb->andWhere('s.territory = :territory')->setParameter('territory', $territory);
        }

        if ($year) {
            $qb->andWhere('YEAR(s.createdAt) = :year')->setParameter('year', $year);
        }

        $qb->groupBy('s.motifCloture');
        $qb->orderBy('s.motifCloture');

        return $qb->getQuery()
            ->getResult();
    }

    /**
     * @throws NonUniqueResultException
     */
    public function findOneOpenedByMailOccupant(string $email): ?Signalement
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.mailOccupant = :email')
            ->setParameter('email', $email)
            ->andWhere('s.statut NOT IN (:statusList)')
            ->setParameter('statusList', [SignalementStatus::ARCHIVED, SignalementStatus::CLOSED, SignalementStatus::REFUSED, SignalementStatus::DRAFT, SignalementStatus::DRAFT_ARCHIVED])
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @throws NonUniqueResultException
     */
    public function findOneOpenedByMailDeclarant(string $email): ?Signalement
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.mailDeclarant = :email')
            ->setParameter('email', $email)
            ->andWhere('s.statut NOT IN (:statusList)')
            ->setParameter('statusList', [SignalementStatus::ARCHIVED, SignalementStatus::CLOSED, SignalementStatus::REFUSED, SignalementStatus::DRAFT, SignalementStatus::DRAFT_ARCHIVED])
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @param array<string, mixed> $options
     */
    public function findSignalementAffectationListPaginator(
        User $user,
        array $options,
    ): Paginator {
        $maxResult = $options['maxItemsPerPage'] ?? SignalementAffectationListView::MAX_LIST_PAGINATION;
        $page = \array_key_exists('page', $options) ? (int) $options['page'] : 1;
        $firstResult = (($page < 1 ? 1 : $page) - 1) * $maxResult;
        $qb = $this->findSignalementAffectationQueryBuilder($user, $options);
        $qb
            ->setFirstResult($firstResult)
            ->setMaxResults($maxResult)
            ->getQuery();

        return new Paginator($qb, true);
    }

    /**
     * @param array<string, mixed> $options
     */
    public function findSignalementAffectationQueryBuilder(
        User $user,
        array $options,
    ): QueryBuilder {
        $qb = $this->createQueryBuilder('s');
        $qb->select('
            DISTINCT s.id,
            s.uuid,
            s.reference,
            s.createdAt,
            s.statut,
            s.score,
            s.isNotOccupant,
            s.nomOccupant,
            s.prenomOccupant,
            s.adresseOccupant,
            s.cpOccupant,
            s.villeOccupant,
            s.lastSuiviAt,
            s.lastSuiviBy,
            s.lastSuiviIsPublic,
            s.profileDeclarant,
            territory.id as territoryId,
            GROUP_CONCAT(DISTINCT CONCAT(p.nom, :concat_separator, a.statut) SEPARATOR :group_concat_separator) as rawAffectations,
            GROUP_CONCAT(DISTINCT p.nom SEPARATOR :group_concat_separator) as affectationPartnerName,
            GROUP_CONCAT(DISTINCT a.statut SEPARATOR :group_concat_separator) as affectationStatus,
            GROUP_CONCAT(DISTINCT sq.qualification SEPARATOR :group_concat_separator) as qualifications,
            GROUP_CONCAT(DISTINCT sq.status SEPARATOR :group_concat_separator) as qualificationsStatuses,
            GROUP_CONCAT(DISTINCT i.concludeProcedure ORDER BY i.scheduledAt DESC SEPARATOR :group_concat_separator) as conclusionsProcedure')
            ->leftJoin('s.affectations', 'a')
            ->leftJoin('a.partner', 'p')
            ->leftJoin('s.signalementQualifications', 'sq', 'WITH', 'sq.status LIKE \'%AVEREE%\' OR sq.status LIKE \'%CHECK%\'')
            ->leftJoin('s.interventions', 'i', 'WITH', 'i.type LIKE \'VISITE\' OR i.type LIKE \'ARRETE_PREFECTORAL\'')
            ->leftJoin('s.territory', 'territory')
            ->where('s.statut NOT IN (:statusList)')
            ->groupBy('s.id')
            ->setParameter('concat_separator', SignalementAffectationListView::SEPARATOR_CONCAT)
            ->setParameter('group_concat_separator', SignalementAffectationListView::SEPARATOR_GROUP_CONCAT);

        if ($user->isTerritoryAdmin()) {
            if (empty($options['territories'])) {
                $qb->andWhere('s.territory IN (:territories)')->setParameter('territories', $user->getPartnersTerritories());
            }
        } elseif ($user->isUserPartner() || $user->isPartnerAdmin()) {
            if (empty($options['territories'])) {
                $qb->andWhere('s.territory IN (:territories)')->setParameter('territories', $user->getPartnersTerritories());
            }
            $statuses = [];
            if (!empty($options['statuses'])) {
                $statuses = array_map(function ($status) {
                    return SignalementStatus::tryFrom($status)?->mapAffectationStatus();
                }, $options['statuses']);
            }

            $subQueryBuilder = $this->_em->createQueryBuilder()
                ->select('DISTINCT IDENTITY(a2.signalement)')
                ->from(Affectation::class, 'a2')
                ->where('a2.partner IN (:partners)');

            if (!empty($options['statuses'])) {
                $subQueryBuilder->andWhere('a2.statut IN (:statut_affectation)');
            }
            $qb->andWhere('s.id IN ('.$subQueryBuilder->getDQL().')');

            $qb->setParameter('partners', $user->getPartners());
            if (!empty($options['statuses'])) {
                $qb->setParameter('statut_affectation', $statuses);
            }
        }

        if (!empty($options['bailleurSocial'])) {
            $qb->andWhere('s.bailleur = :bailleur')
            ->setParameter('bailleur', $options['bailleurSocial']);
        }
        $qb->setParameter('statusList', [SignalementStatus::ARCHIVED, SignalementStatus::DRAFT, SignalementStatus::DRAFT_ARCHIVED]);
        $qb = $this->searchFilter->applyFilters($qb, $options, $user);

        if (isset($options['sortBy'])) {
            switch ($options['sortBy']) {
                case 'reference':
                    $qb
                        ->orderBy('CAST(SUBSTRING_INDEX(s.reference, \'-\', 1) AS UNSIGNED)', $options['orderBy'])
                        ->addOrderBy('CAST(SUBSTRING_INDEX(s.reference, \'-\', -1) AS UNSIGNED)', $options['orderBy']);
                    break;
                case 'nomOccupant':
                    $qb->orderBy('s.nomOccupant', $options['orderBy']);
                    break;
                case 'createdAt':
                    $qb->orderBy('s.createdAt', $options['orderBy']);
                    break;
                case 'lastSuiviAt':
                    $qb->orderBy('s.lastSuiviAt', $options['orderBy']);
                    break;
                case 'villeOccupant':
                    $qb->orderBy('s.villeOccupant', $options['orderBy']);
                    break;
                default:
                    $qb->orderBy('s.createdAt', 'DESC');
            }
        } else {
            $qb->orderBy('s.createdAt', 'DESC');
        }

        return $qb;
    }

    /**
     * @param array<string, mixed> $options
     *
     * @throws Exception
     */
    public function findSignalementAffectationIterable(User $user, array $options): \Generator
    {
        // temporary increase the group_concat_max_len to a higher value, for texts in GROUP_CONCAT
        $connection = $this->getEntityManager()->getConnection();
        $sql = 'SET SESSION group_concat_max_len=32505856';
        $connection->prepare($sql)->executeQuery();

        $qb = $this->findSignalementAffectationQueryBuilder($user, $options);

        $qb->addSelect(
            's.details,
            s.telOccupant,
            s.telOccupantBis,
            s.mailOccupant,
            s.cpOccupant,
            s.inseeOccupant,
            s.etageOccupant,
            s.escalierOccupant,
            s.numAppartOccupant,
            s.adresseAutreOccupant,
            s.isProprioAverti,
            s.nbOccupantsLogement,
            s.nbEnfantsM6,
            s.nbEnfantsP6,
            s.isAllocataire,
            s.numAllocataire,
            s.natureLogement,
            s.superficie,
            s.nomProprio,
            s.isLogementSocial,
            s.isPreavisDepart,
            s.isRelogement,
            s.nomDeclarant,
            s.mailDeclarant,
            s.structureDeclarant,
            s.lienDeclarantOccupant,
            s.modifiedAt,
            s.closedAt,
            s.motifCloture,
            s.comCloture,
            s.geoloc,
            s.typeCompositionLogement,
            s.informationProcedure,
            s.debutDesordres,
            GROUP_CONCAT(DISTINCT situations.label SEPARATOR :group_concat_separator_1) as oldSituations,
            GROUP_CONCAT(DISTINCT criteres.label SEPARATOR :group_concat_separator_1) as oldCriteres,
            GROUP_CONCAT(DISTINCT desordreCategories.label SEPARATOR :group_concat_separator_1) as listDesordreCategories,
            GROUP_CONCAT(DISTINCT desordreCriteres.labelCritere SEPARATOR :group_concat_separator_1) as listDesordreCriteres,
            GROUP_CONCAT(DISTINCT tags.label SEPARATOR :group_concat_separator_1) as etiquettes,
            MAX(vli.occupantPresent) AS interventionOccupantPresent,
            MAX(vli.concludeProcedure) AS interventionConcludeProcedure,
            MAX(vli.details) AS interventionDetails,
            MAX(vli.status) AS interventionStatus,
            MAX(vli.scheduledAt) AS interventionScheduledAt,
            MAX(vli.nbVisites) AS interventionNbVisites
            '
        )->leftJoin('s.situations', 'situations')
            ->leftJoin('s.criteres', 'criteres')
            ->leftJoin('s.desordreCategories', 'desordreCategories')
            ->leftJoin('s.desordreCriteres', 'desordreCriteres')
            ->leftJoin('s.tags', 'tags')
            ->leftJoin(ViewLatestIntervention::class, 'vli', 'WITH', 'vli.signalementId = s.id')
            ->setParameter('concat_separator', SignalementAffectationListView::SEPARATOR_CONCAT)
            ->setParameter('group_concat_separator_1', SignalementExport::SEPARATOR_GROUP_CONCAT);

        return $qb->getQuery()->toIterable();
    }

    /**
     * @return array<int, array<string, mixed>>|int|string
     */
    public function findCities(User $user, ?Territory $territory = null): array|int|string
    {
        return $this->findCommunes($user, $territory, 's.villeOccupant', 'city');
    }

    /**
     * @return array<int, array<string, mixed>>|int|string
     */
    public function findZipcodes(User $user, ?Territory $territory = null): array|int|string
    {
        return $this->findCommunes($user, $territory, 's.cpOccupant', 'zipcode');
    }

    /**
     * @return array<int, array<string, mixed>>|int|string
     */
    public function findCommunes(
        User $user,
        ?Territory $territory = null,
        ?string $field = null,
        ?string $alias = null,
    ): array|int|string {
        $qb = $this->createQueryBuilder('s')
            ->select($field.' '.$alias)
            ->where('s.statut NOT IN (:statutList)')
            ->setParameter('statutList', [SignalementStatus::ARCHIVED, SignalementStatus::DRAFT, SignalementStatus::DRAFT_ARCHIVED]);
        if (!$user->isSuperAdmin() && !$user->isTerritoryAdmin()) {
            $qb->leftJoin('s.affectations', 'affectations')
                ->leftJoin('affectations.partner', 'partner')
                ->andWhere('partner IN (:partners)')
                ->setParameter('partners', $user->getPartners());
        }
        if ($territory) {
            $qb->andWhere('s.territory = :territory')
                ->setParameter('territory', $territory);
        } elseif (!$user->isSuperAdmin()) {
            $qb->andWhere('s.territory IN (:territories)')
                ->setParameter('territories', $user->getPartnersTerritories());
        }

        return $qb
            ->groupBy($field)
            ->orderBy($field, 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findOneByCodeForPublic(string $code): ?Signalement
    {
        $qb = $this->createQueryBuilder('s')
            ->andWhere('s.codeSuivi = :code')
            ->setParameter('code', $code)
            ->leftJoin('s.suivis', 'suivis', Join::WITH, 'suivis.isPublic = 1')
            ->addSelect('suivis')
            ->andWhere('s.statut NOT IN (:statutDraft)')
            ->setParameter('statutDraft', [SignalementStatus::DRAFT, SignalementStatus::DRAFT_ARCHIVED]);

        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * @return array<string, string>|null
     *
     * @throws TransactionRequiredException
     * @throws NonUniqueResultException
     */
    public function findLastReferenceByTerritory(Territory $territory): ?array
    {
        $year = (new \DateTime())->format('Y');
        $queryBuilder = $this->createQueryBuilder('s')
            ->select('s.reference')
            ->addSelect("SUBSTRING_INDEX(s.reference, '-', 1) AS year")
            ->addSelect("CAST(SUBSTRING_INDEX(s.reference, '-', -1) AS SIGNED) AS reference_index")
            ->where('YEAR(s.createdAt) = :year')
            ->setParameter('year', $year)
            ->andWhere('s.territory = :territory')
            ->setParameter('territory', $territory)
            ->orderBy('reference_index', 'DESC')
            ->setMaxResults(1);

        return $queryBuilder
            ->getQuery()
            ->setLockMode(LockMode::PESSIMISTIC_WRITE)
            ->getOneOrNullResult();
    }

    public function getAverageCriticite(
        ?Territory $territory,
        ?ArrayCollection $partners,
        bool $removeImported = false,
        bool $removeDraft = true,
    ): ?float {
        $qb = $this->createQueryBuilder('s');
        $qb->select('AVG(s.score)');

        if ($removeImported) {
            $qb->andWhere('s.isImported IS NULL OR s.isImported = 0');
        }

        if ($removeDraft) {
            $qb->andWhere('s.statut NOT IN (:statusDraft)')->setParameter('statusDraft', [SignalementStatus::DRAFT, SignalementStatus::DRAFT_ARCHIVED]);
        }
        if ($territory) {
            $qb->andWhere('s.territory = :territory')->setParameter('territory', $territory);
        }
        if ($partners && $partners->count() > 0) {
            $qb->leftJoin('s.affectations', 'affectations')
                ->leftJoin('affectations.partner', 'partner')
                ->andWhere('partner IN (:partners)')
                ->setParameter('partners', $partners);
        }

        return $qb->getQuery()
            ->getSingleScalarResult();
    }

    public function getAverageDaysValidation(?Territory $territory, ?ArrayCollection $partners, bool $removeImported = false,
        bool $removeDraft = true, ): ?float
    {
        return $this->getAverageDayResult('validatedAt', $territory, $partners, $removeImported, $removeDraft);
    }

    public function getAverageDaysClosure(?Territory $territory, ?ArrayCollection $partners, bool $removeImported = false,
        bool $removeDraft = true, ): ?float
    {
        return $this->getAverageDayResult('closedAt', $territory, $partners, $removeImported, $removeDraft);
    }

    private function getAverageDayResult(
        string $field, ?Territory $territory, ?ArrayCollection $partners, bool $removeImported = false, bool $removeDraft = true, ): ?float
    {
        $qb = $this->createQueryBuilder('s');
        $qb->select('AVG(datediff(s.'.$field.', s.createdAt))');

        $qb->andWhere('s.'.$field.' IS NOT NULL');

        if ($removeDraft) {
            $qb->andWhere('s.statut NOT IN (:statusDraft)')->setParameter('statusDraft', [SignalementStatus::DRAFT, SignalementStatus::DRAFT_ARCHIVED]);
        }
        if ($removeImported) {
            $qb->andWhere('s.isImported IS NULL OR s.isImported = 0');
        }
        if ($territory) {
            $qb->andWhere('s.territory = :territory')->setParameter('territory', $territory);
        }
        if ($partners && $partners->count() > 0) {
            $qb->leftJoin('s.affectations', 'affectations')
                ->leftJoin('affectations.partner', 'partner')
                ->andWhere('partner IN (:partners)')
                ->setParameter('partners', $partners);
        }

        return $qb->getQuery()
            ->getSingleScalarResult();
    }

    public function countFiltered(StatisticsFilters $statisticsFilters): ?int
    {
        $qb = $this->createQueryBuilder('s');

        $qb->select('COUNT(s.id)');
        $qb = self::addFiltersToQueryBuilder($qb, $statisticsFilters);

        return $qb->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function countByMonthFiltered(StatisticsFilters $statisticsFilters): array
    {
        $qb = $this->createQueryBuilder('s');
        $qb->select('COUNT(s.id) AS count, MONTH(s.createdAt) AS month, YEAR(s.createdAt) AS year');

        $qb = self::addFiltersToQueryBuilder($qb, $statisticsFilters);

        $qb->groupBy('month')
            ->addGroupBy('year');

        $qb->orderBy('year')
            ->addOrderBy('month');

        return $qb->getQuery()
            ->getResult();
    }

    public function getAverageCriticiteFiltered(StatisticsFilters $statisticsFilters): ?float
    {
        $qb = $this->createQueryBuilder('s');

        $qb->select('AVG(s.score)');
        $qb->andWhere('s.score IS NOT NULL');

        $qb = self::addFiltersToQueryBuilder($qb, $statisticsFilters);

        return $qb->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function countBySituationFiltered(StatisticsFilters $statisticsFilters): array
    {
        $qb = $this->createQueryBuilder('s');
        $qb->select('COUNT(s.id) AS count, sit.id, sit.menuLabel');
        $qb->leftJoin('s.situations', 'sit');

        $qb = self::addFiltersToQueryBuilder($qb, $statisticsFilters);

        $qb->andWhere('sit.isActive = :isActive')->setParameter('isActive', true);
        $qb->groupBy('sit.id');

        return $qb->getQuery()
            ->getResult();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function countByCriticiteFiltered(StatisticsFilters $statisticsFilters): array
    {
        $qb = $this->createQueryBuilder('s');
        $qb->select('COUNT(s.id) AS count, crit.id, crit.label');
        $qb->leftJoin('s.criticites', 'crit');

        $qb = self::addFiltersToQueryBuilder($qb, $statisticsFilters);

        $qb->andWhere('crit.isArchive = :isArchive')->setParameter('isArchive', false);
        $qb->groupBy('crit.id');

        return $qb->getQuery()
            ->getResult();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function countByStatusFiltered(StatisticsFilters $statisticsFilters): array
    {
        $qb = $this->createQueryBuilder('s');
        $qb->select('COUNT(s.id) as count')
            ->addSelect('s.statut');

        $qb = self::addFiltersToQueryBuilder($qb, $statisticsFilters);

        $qb->indexBy('s', 's.statut');
        $qb->groupBy('s.statut');

        return $qb->getQuery()
            ->getResult();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function countByCriticitePercentFiltered(StatisticsFilters $statisticsFilters): array
    {
        $qb = $this->createQueryBuilder('s');
        $qb->select('COUNT(s.id) as count')
            ->addSelect('case
                when s.score >= 0 and s.score < 10 then \''.CriticitePercentStatisticProvider::CRITICITE_VERY_WEAK.'\'
                when s.score >= 10 and s.score < 30 then \''.CriticitePercentStatisticProvider::CRITICITE_WEAK.'\'
                else \''.CriticitePercentStatisticProvider::CRITICITE_STRONG.'\'
                end as range');

        $qb = self::addFiltersToQueryBuilder($qb, $statisticsFilters);

        $qb->groupBy('range');

        return $qb->getQuery()
            ->getResult();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function countByVisiteFiltered(StatisticsFilters $statisticsFilters): array
    {
        $qb = $this->createQueryBuilder('s');
        $qb->select('COUNT(s.id) as count')
            ->addSelect(
                'case
                when i.id IS NULL then \'Non\'
                else \'Oui\'
                end as visite'
            )
            ->leftJoin('s.interventions', 'i');

        $qb = self::addFiltersToQueryBuilder($qb, $statisticsFilters);

        $qb->groupBy('visite');

        return $qb->getQuery()
            ->getResult();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function countByMotifClotureFiltered(StatisticsFilters $statisticsFilters): array
    {
        $qb = $this->createQueryBuilder('s');
        $qb->select('COUNT(s.id) AS count, s.motifCloture')

            ->where('s.motifCloture IS NOT NULL')
            ->andWhere('s.motifCloture != \'0\'')
            ->andWhere('s.closedAt IS NOT NULL');

        $qb = self::addFiltersToQueryBuilder($qb, $statisticsFilters);

        $qb->groupBy('s.motifCloture');

        return $qb->getQuery()
            ->getResult();
    }

    public static function addFiltersToQueryBuilder(QueryBuilder $qb, StatisticsFilters $filters): QueryBuilder
    {
        // Is the status defined?
        if ('' != $filters->getStatut() && 'all' != $filters->getStatut()) {
            $statutParameter = [];
            switch ($filters->getStatut()) {
                case 'new':
                    $statutParameter[] = SignalementStatus::NEED_VALIDATION;
                    break;
                case 'active':
                    $statutParameter[] = SignalementStatus::ACTIVE;
                    break;
                case 'closed':
                    $statutParameter[] = SignalementStatus::CLOSED;
                    break;
                default:
                    break;
            }
            // If we count the Refused status
            if ($filters->isCountRefused()) {
                $statutParameter[] = SignalementStatus::REFUSED;
            }
            // If we count the Archived status
            if ($filters->isCountArchived()) {
                $statutParameter[] = SignalementStatus::ARCHIVED;
            }

            $qb->andWhere('s.statut IN (:statutSelected)')
                ->setParameter('statutSelected', $statutParameter);

        // We're supposed to keep all statuses, but we remove at least the Archived
        } else {
            // If we don't want Refused status
            if (!$filters->isCountRefused()) {
                $qb->andWhere('s.statut != :statutRefused')
                    ->setParameter('statutRefused', SignalementStatus::REFUSED);
            }
            // If we don't want Archived status
            if (!$filters->isCountArchived()) {
                $qb->andWhere('s.statut != :statutArchived')
                    ->setParameter('statutArchived', SignalementStatus::ARCHIVED);
            }
            // Pour l'instant on exclue de base les brouillons
            $qb->andWhere('s.statut NOT IN (:statutDraft)')
                ->setParameter('statutDraft', [SignalementStatus::DRAFT, SignalementStatus::DRAFT_ARCHIVED]);
        }

        // Filter on creation date
        if (null !== $filters->getDateStart()) {
            $qb->andWhere('s.createdAt >= :dateStart')
                ->setParameter('dateStart', $filters->getDateStart())
                ->andWhere('s.createdAt <= :dateEnd')
                ->setParameter('dateEnd', $filters->getDateEnd());
        }

        // Filter on Signalement type (logement social)
        if ('' != $filters->getType() && 'all' != $filters->getType()) {
            switch ($filters->getType()) {
                case 'public':
                    $qb->andWhere('s.isLogementSocial = :statutLogementSocial')
                        ->setParameter('statutLogementSocial', true);
                    break;
                case 'private':
                    $qb->andWhere('s.isLogementSocial = :statutLogementSocial')
                        ->setParameter('statutLogementSocial', false);
                    break;
                case 'unset':
                    $qb->andWhere('s.isLogementSocial is NULL');
                    break;
                default:
                    break;
            }
        }

        if ($filters->getTerritory()) {
            $qb->andWhere('s.territory = :territory')
                ->setParameter('territory', $filters->getTerritory());
        }

        if ($filters->getEtiquettes()) {
            $qb->leftJoin('s.tags', 'tags');
            $qb->andWhere('tags IN (:tags)')
                ->setParameter('tags', $filters->getEtiquettes());
        }

        if ($filters->getCommunes()) {
            $communes = [];
            foreach ($filters->getCommunes() as $city) {
                $communes[] = $filters->getCommunes();
                if (isset(CommuneHelper::COMMUNES_ARRONDISSEMENTS[$city])) {
                    $communes = array_merge($communes, CommuneHelper::COMMUNES_ARRONDISSEMENTS[$city]);
                }
            }
            $qb->andWhere('s.villeOccupant IN (:communes)')
                ->setParameter('communes', $communes);
        }

        if ($filters->getEpcis()) {
            $subQuery = $qb->getEntityManager()->createQueryBuilder()
            ->select('DISTINCT s2.id')
            ->from(Signalement::class, 's2')
            ->innerJoin(
                Commune::class,
                'c2',
                'WITH',
                's2.cpOccupant = c2.codePostal AND s2.inseeOccupant = c2.codeInsee AND c2.epci IN (:epcis)'
            );
            $qb->andWhere('s.id IN ('.$subQuery->getDQL().')')->setParameter('epcis', $filters->getEpcis());
        }

        if ($filters->getPartners() && $filters->getPartners()->count() > 0) {
            $qb->leftJoin('s.affectations', 'affectations')
                ->leftJoin('affectations.partner', 'partner')
                ->andWhere('partner IN (:partners)')
                ->setParameter('partners', $filters->getPartners());
        }

        return $qb;
    }

    /**
     * @throws NonUniqueResultException
     */
    public function findByReferenceChunk(Territory $territory, string $chunkReference): ?Signalement
    {
        return $this->createQueryBuilder('s')
            ->where('s.territory = :territory')
            ->andWhere('s.reference LIKE :reference')
            ->setParameter('territory', $territory)
            ->setParameter('reference', '%'.$chunkReference.'%')
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @return array<int, array<string, mixed>>
     *
     * @throws Exception
     */
    public function countSignalementTerritory(): array
    {
        $connexion = $this->getEntityManager()->getConnection();
        $noAffectedSql = 'SELECT COUNT(s2.id)
                   FROM signalement s2
                   INNER JOIN territory t2 ON t2.id = s2.territory_id
                   WHERE (s2.statut = :statut_2 OR s2.statut = :statut_1) AND s2.territory_id = t1.id
                   AND s2.id NOT IN (SELECT a.signalement_id FROM affectation a)';

        $sql = 'SELECT t1.id, t1.zip, t1.name as territory_name,
                CONCAT(t1.zip, " - ", t1.name) as label,
                SUM(CASE WHEN s1.statut = :statut_1 THEN 1 ELSE 0 END) AS new,
                ('.$noAffectedSql.') AS no_affected
                FROM signalement s1
                INNER JOIN territory t1 ON t1.id = s1.territory_id
                GROUP BY t1.id, t1.zip, t1.name
                ORDER BY t1.name;';

        $statement = $connexion->prepare($sql);

        return $statement->executeQuery([
            'statut_1' => SignalementStatus::NEED_VALIDATION->value,
            'statut_2' => SignalementStatus::ACTIVE->value,
        ])->fetchAllAssociative();
    }

    /**
     * @param array<int, int> $territories
     *
     * @return array<int, array<string, mixed>>
     */
    public function countSignalementAcceptedNoSuivi(array $territories): array
    {
        $subquery = $this->_em->createQueryBuilder()
            ->select('IDENTITY(su.signalement)')
            ->from(Suivi::class, 'su')
            ->innerJoin('su.signalement', 'sig')
            ->where('sig.territory IN (:territories_1)')
            ->andWhere('sig.statut = :statut')
            ->andWhere('su.type IN (:suivi_type)')
            ->setParameter('suivi_type', [Suivi::TYPE_USAGER, Suivi::TYPE_PARTNER])
            ->setParameter('statut', SignalementStatus::ACTIVE)
            ->setParameter('territories_1', $territories)
            ->distinct();

        $queryBuilder = $this->createQueryBuilder('s')
            ->select('COUNT(s.id) as count_no_suivi, p.nom')
            ->innerJoin('s.affectations', 'a')
            ->innerJoin('a.partner', 'p')
            ->where('s.statut = :statut')
            ->andWhere('p.territory IN (:territories)')
            ->andWhere('s.id NOT IN (:subquery)')
            ->setParameter('statut', SignalementStatus::ACTIVE)
            ->setParameter('subquery', $subquery->getQuery()->getSingleColumnResult())
            ->setParameter('territories', $territories)
            ->groupBy('p.nom');

        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * @param array<int, int> $territories
     *
     * @throws NonUniqueResultException
     * @throws NoResultException
     * @throws QueryException
     */
    public function countSignalementByStatus(array $territories): CountSignalement
    {
        $qb = $this->createQueryBuilder('s');
        $qb->select(
            \sprintf(
                'NEW %s(
                COUNT(s.id),
                SUM(CASE WHEN s.statut = :new     THEN 1 ELSE 0 END),
                SUM(CASE WHEN s.statut = :active  THEN 1 ELSE 0 END),
                SUM(CASE WHEN s.statut = :closed  THEN 1 ELSE 0 END),
                SUM(CASE WHEN s.statut = :refused THEN 1 ELSE 0 END))',
                CountSignalement::class
            )
        )
            ->setParameter('new', SignalementStatus::NEED_VALIDATION)
            ->setParameter('active', SignalementStatus::ACTIVE)
            ->setParameter('closed', SignalementStatus::CLOSED)
            ->setParameter('refused', SignalementStatus::REFUSED)
            ->where('s.statut NOT IN (:statutList)')
            ->setParameter('statutList', [SignalementStatus::ARCHIVED, SignalementStatus::DRAFT, SignalementStatus::DRAFT_ARCHIVED]);

        if (\count($territories)) {
            $qb->andWhere('s.territory IN (:territories)')->setParameter('territories', $territories);
        }

        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * @param array<int, Territory> $territories
     */
    public function countSignalementUsagerAbandonProcedure(array $territories): ?int
    {
        $qb = $this->createQueryBuilder('s');
        $qb->select('COUNT(s.id)')
            ->where('s.statut IN (:statutList)')
            ->andWhere('s.isUsagerAbandonProcedure = 1')
            ->setParameter('statutList', [SignalementStatus::ACTIVE]);

        if (\count($territories)) {
            $qb->andWhere('s.territory IN (:territories)')->setParameter('territories', $territories);
        }

        return $qb->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @param array<int, int> $ids
     *
     * @return array<int, Signalement>
     */
    public function findAllByIds(array $ids): array
    {
        return $this->createQueryBuilder('s')
            ->where('s.id IN (:ids)')
            ->setParameter('ids', $ids)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return array<int, Signalement>
     */
    public function findWithNoGeolocalisation(?Territory $territory = null): array
    {
        $qb = $this->createQueryBuilder('s')
            ->where('s.inseeOccupant LIKE :insee_occupant OR s.inseeOccupant IS NULL')
            ->setParameter('insee_occupant', '%#ERROR%');

        if ($territory) {
            $qb->andWhere('s.territory = :territory')
                ->setParameter('territory', $territory)
                ->setParameter('territory', $territory);
        }

        return $qb->getQuery()->getResult();
    }

    public function findOneForEmailAndAddress(
        string $email,
        string $address,
        string $zipcode,
        string $city,
    ): ?Signalement {
        $qb = $this->createQueryBuilder('s')
            ->andWhere('s.mailDeclarant = :email OR s.mailOccupant = :email')->setParameter('email', $email)
            ->andWhere('s.adresseOccupant = :address')->setParameter('address', $address)
            ->andWhere('s.cpOccupant = :zipcode')->setParameter('zipcode', $zipcode)
            ->andWhere('s.villeOccupant = :city')->setParameter('city', $city)
            ->andWhere('s.statut NOT IN (:statutList)')->setParameter('statutList', [SignalementStatus::ARCHIVED, SignalementStatus::DRAFT, SignalementStatus::DRAFT_ARCHIVED]);

        $list = $qb->addOrderBy('s.createdAt', 'DESC')
            ->getQuery()->getResult();
        $statutsList = [
            SignalementStatus::ACTIVE,
            SignalementStatus::NEED_VALIDATION,
            SignalementStatus::CLOSED,
            SignalementStatus::REFUSED,
        ];
        foreach ($statutsList as $statut) {
            foreach ($list as $item) {
                if ($item->getStatut() === $statut) {
                    return $item;
                }
            }
        }

        return null;
    }

    /**
     * @return array<int, Signalement>
     */
    public function findAllForEmailAndAddress(
        ?string $email,
        ?string $address,
        ?string $zipcode,
        ?string $city,
        bool $isTiersDeclarant = true,
    ): array {
        if (empty($email) || empty($address) || empty($zipcode) || empty($city)) {
            return [];
        }

        $city = CommuneHelper::getCommuneFromArrondissement($city);

        $qb = $this->createQueryBuilder('s');
        if ($isTiersDeclarant) {
            $qb->andWhere('s.mailDeclarant = :email')->setParameter('email', $email);
        } else {
            $qb->andWhere('s.mailOccupant = :email')->setParameter('email', $email);
        }
        $qb->andWhere('LOWER(s.adresseOccupant) = :address')->setParameter('address', strtolower($address))
            ->andWhere('s.cpOccupant = :zipcode')->setParameter('zipcode', $zipcode)
            ->andWhere('LOWER(s.villeOccupant) = :city')->setParameter('city', strtolower($city))
            ->andWhere('s.statut IN (:statusSignalement)')
            ->setParameter(
                'statusSignalement',
                [
                    SignalementStatus::ACTIVE,
                    SignalementStatus::NEED_VALIDATION,
                ]
            );

        if ($isTiersDeclarant) {
            $qb->addOrderBy('s.createdAt', 'DESC');
        } else {
            $qb->addOrderBy('s.lastSuiviAt', 'DESC');
            $qb->setMaxResults(1);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * @param array<int, string> $needles
     *
     * @return array<int, Signalement>
     */
    public function findByEmailContainStrings(array $needles, string $field, bool $strict = false): array
    {
        if (empty($needles)) {
            return [];
        }

        $qb = $this->createQueryBuilder('s');
        foreach ($needles as $index => $needle) {
            $needle = $strict ? $needle : '%'.$needle.'%';
            $qb->orWhere('s.'.$field.' LIKE :needle'.$index)
                ->setParameter('needle'.$index, $needle);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * @return array<int, Signalement>
     */
    public function findSignalementsWithFilesToUploadOnIdoss(Partner $partner): array
    {
        $qb = $this->createQueryBuilder('s')
            ->select('s', 'f')
            ->innerJoin('s.files', 'f')
            ->innerJoin('s.affectations', 'a')
            ->where("f.synchroData IS NULL OR (JSON_CONTAINS_PATH(f.synchroData, 'one', '$.".IdossService::TYPE_SERVICE."') = 0)")
            ->andWhere("JSON_CONTAINS_PATH(s.synchroData, 'one', '$.".IdossService::TYPE_SERVICE."') = 1")
            ->andWhere('a.partner = :partner')
            ->setParameter('partner', $partner)
        ;

        return $qb->getQuery()->getResult();
    }

    public function findFilteredPaginatedDrafts(
        SearchDraft $searchDraft,
        int $maxResult,
    ): Paginator {
        $queryBuilder = $this->createQueryBuilder('s');
        $queryBuilder
            ->where('s.statut IN (:status_list)')
            ->andWhere('s.createdBy = :user')
            ->setParameter('status_list', [SignalementStatus::DRAFT, SignalementStatus::NEED_VALIDATION])
            ->setParameter('user', $searchDraft->getUser());

        if (!empty($searchDraft->getOrderType())) {
            [$orderField, $orderDirection] = explode('-', $searchDraft->getOrderType());
            $queryBuilder->orderBy($orderField, $orderDirection);
        } else {
            $queryBuilder->orderBy('s.createdAt', 'DESC');
        }

        $firstResult = ($searchDraft->getPage() - 1) * $maxResult;
        $queryBuilder->setFirstResult($firstResult)->setMaxResults($maxResult);

        return new Paginator($queryBuilder->getQuery(), false);
    }

    public function findFilteredArchivedPaginated(
        SearchArchivedSignalement $searchArchivedSignalement,
        int $maxResult,
    ): Paginator {
        return $this->findAllArchived(
            page: $searchArchivedSignalement->getPage(),
            maxResult: $maxResult,
            territory: $searchArchivedSignalement->getTerritory(),
            referenceTerms: $searchArchivedSignalement->getQueryReference(),
            searchArchivedSignalement: $searchArchivedSignalement,
        );
    }

    public function findAllArchived(
        int $page,
        int $maxResult,
        ?Territory $territory,
        ?string $referenceTerms,
        ?SearchArchivedSignalement $searchArchivedSignalement = null,
    ): Paginator {
        $queryBuilder = $this->createQueryBuilder('s');

        $queryBuilder
            ->where('s.statut = :archived')
            ->setParameter('archived', SignalementStatus::ARCHIVED);

        if (!empty($territory)) {
            $queryBuilder
                ->andWhere('s.territory = :territory')
                ->setParameter('territory', $territory);
        }

        if (!empty($referenceTerms)) {
            $queryBuilder
                ->andWhere('s.reference LIKE :referenceTerms')
                ->setParameter('referenceTerms', $referenceTerms);
        }

        if (!empty($searchArchivedSignalement) && !empty($searchArchivedSignalement->getOrderType())) {
            [$orderField, $orderDirection] = explode('-', $searchArchivedSignalement->getOrderType());
            $queryBuilder->orderBy($orderField, $orderDirection);
        } else {
            $queryBuilder->orderBy('s.createdAt', 'ASC');
        }

        $firstResult = ($page - 1) * $maxResult;
        $queryBuilder->setFirstResult($firstResult)->setMaxResults($maxResult);

        return new Paginator($queryBuilder->getQuery(), false);
    }

    /**
     * @return array<int, Signalement>
     */
    public function findNullBanId(): array
    {
        return $this->createQueryBuilder('s')
            ->select('s.id')
            ->where('s.banIdOccupant IS NULL')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return array<int, Signalement>
     */
    public function findLogementSocialWithoutBailleurLink(): array
    {
        return $this->createQueryBuilder('s')
            ->where('s.isLogementSocial = 1')
            ->andWhere('s.bailleur IS NULL')
            ->andWhere('s.nomProprio IS NOT NULL')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return array<int, Signalement>
     */
    public function findSynchroIdoss(string $status): array
    {
        return $this->createQueryBuilder('s')
            ->select('s.id', 's.uuid', 's.reference', 'j.action', 'j.response', 'j.createdAt', 'j.codeStatus', 'j.partnerId')
            ->innerJoin(JobEvent::class, 'j', 'WITH', 's.id = j.signalementId')
            ->where('j.signalementId = s.id')
            ->andWhere('j.service = :service')
            ->andWhere('j.status = :status')
            ->setParameter('service', IdossService::TYPE_SERVICE)
            ->setParameter('status', $status)
            ->addOrderBy('j.createdAt', 'DESC')
            ->indexBy('s', 's.id')
            ->setMaxResults(100)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return array<int, Signalement>
     */
    public function findSignalementsBetweenDates(\DateTimeImmutable $startDate, \DateTimeImmutable $endDate): array
    {
        $qb = $this->createQueryBuilder('s');

        return $qb
            ->where('s.createdAt BETWEEN :startDate AND :endDate')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->orderBy('s.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return array<int, Signalement>
     */
    public function findSignalementsSplittedCreatedBefore(int $split, Territory $territory): array
    {
        $qb = $this->createQueryBuilder('s')
            ->where('s.territory = :territory')
            ->setParameter('territory', $territory)
            ->orderBy('s.createdAt', 'ASC');

        if (1 === $split) {
            $qb->andWhere('s.createdAt < :beforeDate')->setParameter('beforeDate', '2024-02-01');
            $qb->andWhere('s.createdAt > :afterDate')->setParameter('afterDate', '2023-01-01');
        } elseif (2 === $split) {
            $qb->andWhere('s.createdAt < :beforeDate')->setParameter('beforeDate', '2023-01-01');
            $qb->andWhere('s.createdAt > :afterDate')->setParameter('afterDate', '2021-01-01');
        } elseif (3 === $split) {
            $qb->andWhere('s.createdAt < :beforeDate')->setParameter('beforeDate', '2021-01-01');
        } else {
            $qb->andWhere('s.createdAt < :beforeDate')->setParameter('beforeDate', '2024-02-01');
        }

        return $qb->getQuery()->getResult();
    }

    public function findOneForApi(
        User $user,
        ?string $uuid = null,
        ?string $reference = null,
    ): ?Signalement {
        $qb = $this->findForAPIQueryBuilder($user);
        if ($uuid) {
            $qb->andWhere('s.uuid = :uuid')->setParameter('uuid', $uuid);
        }
        if ($reference) {
            $qb->andWhere('s.reference = :reference')->setParameter('reference', $reference);
        }

        if (count($result = $qb->getQuery()->getResult()) > 0) {
            return current($result);
        }

        return null;
    }

    /**
     * @return array<int, Signalement>
     *
     * @throws \DateMalformedStringException
     */
    public function findAllForApi(User $user, SignalementListQueryParams $signalementListQueryParams): array
    {
        $page = (int) ($signalementListQueryParams->page ?? SignalementListQueryParams::DEFAULT_PAGE);
        $limit = (int) ($signalementListQueryParams->limit ?? SignalementListQueryParams::DEFAULT_LIMIT);

        $offset = ($page - 1) * $limit;
        $qb = $this->findForAPIQueryBuilder($user);

        if (!empty($signalementListQueryParams->dateAffectationDebut)) {
            $qb->andWhere('affectations.createdAt >= :dateAffectationStart')
                ->setParameter('dateAffectationStart', $signalementListQueryParams->dateAffectationDebut);
        }

        if (!empty($signalementListQueryParams->dateAffectationFin)) {
            $dateAffectationEnd = (new \DateTimeImmutable($signalementListQueryParams->dateAffectationFin))
                ->modify('+1 day');

            $qb->andWhere('affectations.createdAt <= :dateAffectationEnd')
                ->setParameter('dateAffectationEnd', $dateAffectationEnd);
        }

        $qb->orderBy('s.createdAt', 'DESC')
            ->setFirstResult($offset)
            ->setMaxResults($limit);

        return $qb->getQuery()->getResult();
    }

    public function findForAPIQueryBuilder(User $user): QueryBuilder
    {
        $partners = $user->getPartners();
        $qb = $this->createQueryBuilder('s');

        return $qb->select('s', 'territory')
            ->leftJoin('s.territory', 'territory')
            ->leftJoin('s.affectations', 'affectations')
            ->where('affectations.partner IN (:partners)')
            ->setParameter('partners', $partners);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function findSignalementsLastSuiviWithSuiviAuto(Territory $territory, int $limit): array
    {
        $connexion = $this->getEntityManager()->getConnection();
        $sql = 'SELECT s.id, s.reference, s.uuid, MAX(su.created_at) as dernier_suivi_date, MAX(su.created_by_id) as dernier_suivi_created_by, MAX(su.description) as dernier_suivi_description
                FROM signalement s
                INNER JOIN suivi su ON su.signalement_id = s.id
                INNER JOIN territory ON s.territory_id = territory.id
                WHERE s.id in (
                        SELECT sutech.signalement_id FROM suivi sutech WHERE sutech.type = :suiviTypeTechnical
                    )
                AND su.id = (
                        SELECT MAX(su2.id)
                        FROM suivi AS su2
                        WHERE su2.signalement_id = su.signalement_id
                    )
                AND s.statut = :statusSignalement
                AND s.territory_id = :territoryId
                AND su.type = :suiviTypeUsager
                GROUP BY s.id
                HAVING dernier_suivi_date > \''.self::DATE_FEEDBACK_USAGER_ONLINE.'\'
                ORDER BY dernier_suivi_date DESC
                LIMIT '.$limit.';';

        $statement = $connexion->prepare($sql);

        return $statement->executeQuery([
            'statusSignalement' => SignalementStatus::ACTIVE->value,
            'territoryId' => $territory->getId(),
            'suiviTypeTechnical' => Suivi::TYPE_TECHNICAL,
            'suiviTypeUsager' => Suivi::TYPE_USAGER,
        ])->fetchAllAssociative();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function findSignalementsLastSuiviByPartnerOlderThan(Territory $territory, int $limit, int $nbDays): array
    {
        $connexion = $this->getEntityManager()->getConnection();
        $sql = 'SELECT s.id, s.reference, s.uuid, MAX(su.created_at) as dernier_suivi_date, MAX(su.created_by_id) as dernier_suivi_created_by, MAX(su.description) as dernier_suivi_description
                FROM signalement s
                INNER JOIN suivi su ON su.signalement_id = s.id
                INNER JOIN territory ON s.territory_id = territory.id
                WHERE su.id = (
                        SELECT MAX(su2.id)
                        FROM suivi AS su2
                        WHERE su2.signalement_id = su.signalement_id
                    )
                AND s.statut = :statusSignalement
                AND s.territory_id = :territoryId
                AND su.type = :suiviTypePartner
                GROUP BY s.id
                HAVING dernier_suivi_date < NOW() - INTERVAL :nbDays DAY
                ORDER BY dernier_suivi_date DESC
                LIMIT '.$limit.';';

        $statement = $connexion->prepare($sql);

        return $statement->executeQuery([
            'statusSignalement' => SignalementStatus::ACTIVE->value,
            'territoryId' => $territory->getId(),
            'suiviTypePartner' => Suivi::TYPE_PARTNER,
            'nbDays' => $nbDays,
        ])->fetchAllAssociative();
    }

    /**
     * @param array<int, SignalementStatus> $exclusiveStatus
     * @param array<int, SignalementStatus> $excludedStatus
     *
     * @return array<int, Signalement>
     */
    public function findOnSameAddress(
        Signalement $signalement,
        array $exclusiveStatus = [SignalementStatus::NEED_VALIDATION, SignalementStatus::ACTIVE],
        array $excludedStatus = [],
        ?User $createdBy = null,
    ): array {
        $qb = $this->createQueryBuilder('s')
            ->where('s.adresseOccupant = :address')
            ->andWhere('s.cpOccupant = :zipcode')
            ->andWhere('s.villeOccupant = :city')
            ->setParameter('address', $signalement->getAdresseOccupant())
            ->setParameter('zipcode', $signalement->getCpOccupant())
            ->setParameter('city', $signalement->getVilleOccupant());

        if (!empty($exclusiveStatus)) {
            $qb->andWhere('s.statut IN (:exclusiveStatus)')
                ->setParameter('exclusiveStatus', $exclusiveStatus);
        }
        if (!empty($excludedStatus)) {
            $qb->andWhere('s.statut NOT IN (:excludedStatus)')
                ->setParameter('excludedStatus', $excludedStatus);
        }

        if (null !== $signalement->getId()) {
            $qb->andWhere('s.id != :id')
            ->setParameter('id', $signalement->getId());
        }

        if (null !== $createdBy) {
            $qb->andWhere('s.createdBy = :user')
            ->setParameter('user', $createdBy);
        }

        return $qb->getQuery()->getResult();
    }
}
