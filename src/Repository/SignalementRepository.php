<?php

namespace App\Repository;

use App\Dto\Api\Request\SignalementListQueryParams;
use App\Dto\CountSignalement;
use App\Dto\StatisticsFilters;
use App\Entity\Affectation;
use App\Entity\Commune;
use App\Entity\EmailDeliveryIssue;
use App\Entity\Enum\AffectationStatus;
use App\Entity\Enum\DesordreCritereZone;
use App\Entity\Enum\Qualification;
use App\Entity\Enum\QualificationStatus;
use App\Entity\Enum\SignalementStatus;
use App\Entity\Enum\SuiviCategory;
use App\Entity\Partner;
use App\Entity\Signalement;
use App\Entity\Suivi;
use App\Entity\Territory;
use App\Entity\User;
use App\Entity\UserSignalementSubscription;
use App\Service\DashboardTabPanel\Kpi\CountAfermer;
use App\Service\DashboardTabPanel\Kpi\CountNouveauxDossiers;
use App\Service\DashboardTabPanel\TabDossier;
use App\Service\DashboardTabPanel\TabQueryParameters;
use App\Service\Interconnection\Idoss\IdossService;
use App\Service\ListFilters\SearchArchivedSignalement;
use App\Service\ListFilters\SearchDraft;
use App\Service\ListFilters\SearchSignalementInjonction;
use App\Service\Security\PartnerAuthorizedResolver;
use App\Service\Signalement\ZipcodeProvider;
use App\Service\Statistics\CriticitePercentStatisticProvider;
use App\Utils\CommuneHelper;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\DBAL\ArrayParameterType;
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
use Symfony\Bundle\SecurityBundle\Security;

/**
 * @method Signalement|null find($id, $lockMode = null, $lockVersion = null)
 * @method Signalement|null findOneBy(array $criteria, array $orderBy = null)
 * @method Signalement[]    findAll()
 * @method Signalement[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SignalementRepository extends ServiceEntityRepository
{
    public const int MARKERS_PAGE_SIZE = 9000; // @todo: is high cause duplicate result, the query findAllWithGeoData should be reviewed
    private const string DATE_FEEDBACK_USAGER_ONLINE = '2023-03-28';

    public function __construct(
        ManagerRegistry $registry,
        private readonly Security $security,
        private readonly PartnerAuthorizedResolver $partnerAuthorizedResolver,
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
     * @throws NonUniqueResultException
     * @throws NoResultException
     */
    public function countAll(
        ?Territory $territory,
        ?ArrayCollection $partners,
    ): int {
        $qb = $this->createQueryBuilder('s');
        $qb->select('COUNT(s.id)');

        $qb->andWhere('s.statut NOT IN (:excludedStatuses)')
            ->setParameter('excludedStatuses', SignalementStatus::excludedStatuses());

        $qb->andWhere('s.isImported IS NULL OR s.isImported = 0');

        if ($territory) {
            $qb->andWhere('s.territory = :territory')->setParameter('territory', $territory);
        }
        if ($partners && $partners->count() > 0) {
            $qb->leftJoin('s.affectations', 'affectations')
                ->leftJoin('affectations.partner', 'partner')
                ->andWhere('partner IN (:partners)')
                ->setParameter('partners', $partners);
        }

        return $qb->getQuery()->getSingleScalarResult();
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
            ->setParameter('statutList', SignalementStatus::excludedStatuses())
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
     * @param array<int, QualificationStatus>|null $qualificationStatuses
     *
     * @return array<int, array<string, mixed>>
     *
     * @throws QueryException
     */
    public function countByStatus(
        array $territories,
        ?ArrayCollection $partners,
        ?int $year = null,
        bool $removeImported = false,
        ?Qualification $qualification = null,
        ?array $qualificationStatuses = null,
        ?bool $keepArchivedSignalements = false,
    ): array {
        $qb = $this->createQueryBuilder('s');
        $qb->select('COUNT(s.id) as count')
            ->addSelect('s.statut');
        if ($keepArchivedSignalements) {
            $qb->andWhere('s.statut NOT IN (:statutList)')
               ->setParameter('statutList', [SignalementStatus::DRAFT, SignalementStatus::DRAFT_ARCHIVED, SignalementStatus::INJONCTION_BAILLEUR]);
        } else {
            $qb->andWhere('s.statut NOT IN (:statutList)')
               ->setParameter('statutList', SignalementStatus::excludedStatuses());
        }

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

        $qb->indexBy('s', 's.statut')->groupBy('s.statut');

        return $qb->getQuery()->getResult();
    }

    /**
     * @throws NonUniqueResultException
     * @throws NoResultException
     */
    public function countValidated(bool $removeImported = false): int
    {
        $notStatus = array_merge([SignalementStatus::NEED_VALIDATION], SignalementStatus::excludedStatuses());
        $qb = $this->createQueryBuilder('s');
        $qb->select('COUNT(s.id)');
        $qb->andWhere('s.statut NOT IN (:notStatus)')
            ->setParameter('notStatus', $notStatus);

        if ($removeImported) {
            $qb->andWhere('s.isImported IS NULL OR s.isImported = 0');
        }

        return $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * @throws NonUniqueResultException
     * @throws NoResultException
     */
    public function countClosed(bool $removeImported = false): int
    {
        $qb = $this->createQueryBuilder('s');
        $qb->select('COUNT(s.id)');
        $qb->andWhere('s.statut = :closedStatus')
            ->setParameter('closedStatus', SignalementStatus::CLOSED);

        if ($removeImported) {
            $qb->andWhere('s.isImported IS NULL OR s.isImported = 0');
        }

        return $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * @throws NonUniqueResultException
     * @throws NoResultException
     */
    public function countRefused(): int
    {
        $qb = $this->createQueryBuilder('s');
        $qb->select('COUNT(s.id)');
        $qb->andWhere('s.statut = :refusedStatus')
            ->setParameter('refusedStatus', SignalementStatus::REFUSED);

        $qb->andWhere('s.isImported IS NULL OR s.isImported = 0');

        return $qb->getQuery()->getSingleScalarResult();
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
            ->setParameter('statutList', SignalementStatus::excludedStatuses());

        if ($removeImported) {
            $qb->andWhere('s.isImported IS NULL OR s.isImported = 0');
        }

        $qb->groupBy('t.id');

        return $qb->getQuery()->getResult();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function countByMonth(?Territory $territory, ?int $year, bool $removeImported = false): array
    {
        $qb = $this->createQueryBuilder('s');
        $qb->select('COUNT(s.id) AS count, MONTH(s.createdAt) AS month, YEAR(s.createdAt) AS year')
            ->where('s.statut NOT IN (:statutList)')
            ->setParameter('statutList', SignalementStatus::excludedStatuses());

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

        return $qb->getQuery()->getResult();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function countBySituation(?Territory $territory, ?int $year, bool $removeImported = false): array
    {
        $qb = $this->createQueryBuilder('s');
        $qb->select('COUNT(s.id) AS count, sit.id, sit.menuLabel')
            ->leftJoin('s.criticites', 'criticites')
            ->leftJoin('criticites.critere', 'critere')
            ->leftJoin('critere.situation', 'sit')
            ->where('s.statut NOT IN (:statutList)')
            ->setParameter('statutList', SignalementStatus::excludedStatuses());

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

        return $qb->getQuery()->getResult();
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
            ->leftJoin('s.criticites', 'criticites')
            ->leftJoin('criticites.critere', 'c')
            ->leftJoin('s.desordrePrecisions', 'dp')
            ->leftJoin('dp.desordreCritere', 'dc')
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
            ->leftJoin('s.desordrePrecisions', 'dp')
            ->leftJoin('dp.desordreCritere', 'desordreCriteres')
            ->where('s.statut NOT IN (:statutList)')
            ->setParameter('statutList', SignalementStatus::excludedStatuses())
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

        return $qb->getQuery()->getResult();
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

        return $qb->getQuery()->getResult();
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
            ->setParameter('statutList', SignalementStatus::excludedStatuses());
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

    /**
     * @throws NonUniqueResultException
     */
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

    /**
     * @throws NonUniqueResultException
     * @throws NoResultException
     */
    public function getAverageCriticite(
        ?Territory $territory,
        ?ArrayCollection $partners,
    ): ?float {
        $qb = $this->createQueryBuilder('s');
        $qb->select('AVG(s.score)');
        $qb->andWhere('s.isImported IS NULL OR s.isImported = 0');
        $qb->andWhere('s.statut NOT IN (:excludedStatuses)')->setParameter('excludedStatuses', SignalementStatus::excludedStatuses());

        if ($territory) {
            $qb->andWhere('s.territory = :territory')->setParameter('territory', $territory);
        }
        if ($partners && $partners->count() > 0) {
            $qb->leftJoin('s.affectations', 'affectations')
                ->leftJoin('affectations.partner', 'partner')
                ->andWhere('partner IN (:partners)')
                ->setParameter('partners', $partners);
        }

        return $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * @throws NonUniqueResultException
     * @throws NoResultException
     */
    public function getAverageDaysValidation(
        ?Territory $territory,
        ?ArrayCollection $partners,
    ): ?float {
        return $this->getAverageDayResult('validatedAt', $territory, $partners);
    }

    /**
     * @throws NonUniqueResultException
     * @throws NoResultException
     */
    public function getAverageDaysClosure(
        ?Territory $territory,
        ?ArrayCollection $partners,
    ): ?float {
        return $this->getAverageDayResult('closedAt', $territory, $partners);
    }

    /**
     * @throws NonUniqueResultException
     * @throws NoResultException
     */
    private function getAverageDayResult(
        string $field,
        ?Territory $territory,
        ?ArrayCollection $partners,
    ): ?float {
        $qb = $this->createQueryBuilder('s');
        $qb->select('AVG(datediff(s.'.$field.', s.createdAt))');
        $qb->andWhere('s.'.$field.' IS NOT NULL');
        $qb->andWhere('s.statut NOT IN (:excludedStatuses)')->setParameter('excludedStatuses', SignalementStatus::excludedStatuses());
        $qb->andWhere('s.isImported IS NULL OR s.isImported = 0');

        if ($territory) {
            $qb->andWhere('s.territory = :territory')->setParameter('territory', $territory);
        }
        if ($partners && $partners->count() > 0) {
            $qb->leftJoin('s.affectations', 'affectations')
                ->leftJoin('affectations.partner', 'partner')
                ->andWhere('partner IN (:partners)')
                ->setParameter('partners', $partners);
        }

        return $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * @throws NonUniqueResultException
     * @throws NoResultException
     */
    public function countFiltered(StatisticsFilters $statisticsFilters): ?int
    {
        $qb = $this->createQueryBuilder('s');

        $qb->select('COUNT(s.id)');
        $qb = self::addFiltersToQueryBuilder($qb, $statisticsFilters);

        return $qb->getQuery()->getSingleScalarResult();
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

        return $qb->getQuery()->getResult();
    }

    /**
     * @throws NonUniqueResultException
     * @throws NoResultException
     */
    public function getAverageCriticiteFiltered(StatisticsFilters $statisticsFilters): ?float
    {
        $qb = $this->createQueryBuilder('s');

        $qb->select('AVG(s.score)');
        $qb->andWhere('s.score IS NOT NULL');

        $qb = self::addFiltersToQueryBuilder($qb, $statisticsFilters);

        return $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function countBySituationFiltered(StatisticsFilters $statisticsFilters): array
    {
        $qb = $this->createQueryBuilder('s');
        $qb->select('COUNT(s.id) AS count, sit.id, sit.menuLabel');
        $qb->leftJoin('s.criticites', 'criticites');
        $qb->leftJoin('criticites.critere', 'critere');
        $qb->leftJoin('critere.situation', 'sit');

        $qb = self::addFiltersToQueryBuilder($qb, $statisticsFilters);

        $qb->andWhere('sit.isActive = :isActive')->setParameter('isActive', true);
        $qb->groupBy('sit.id');

        return $qb->getQuery()->getResult();
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

        return $qb->getQuery()->getResult();
    }

    /**
     * @return array<int, array<string, mixed>>
     *
     * @throws QueryException
     */
    public function countByStatusFiltered(StatisticsFilters $statisticsFilters): array
    {
        $qb = $this->createQueryBuilder('s');
        $qb->select('COUNT(s.id) as count')
            ->addSelect('s.statut');

        $qb = self::addFiltersToQueryBuilder($qb, $statisticsFilters);

        $qb->indexBy('s', 's.statut');
        $qb->groupBy('s.statut');

        return $qb->getQuery()->getResult();
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

        return $qb->getQuery()->getResult();
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

        return $qb->getQuery()->getResult();
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

        return $qb->getQuery()->getResult();
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
            // Pour l'instant on exclue de base les brouillons et injonction bailleur
            $qb->andWhere('s.statut NOT IN (:statutDraft)')
                ->setParameter('statutDraft', [SignalementStatus::DRAFT, SignalementStatus::DRAFT_ARCHIVED, SignalementStatus::INJONCTION_BAILLEUR]);
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
            ->setParameter('statutList', SignalementStatus::excludedStatuses());

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

        return $qb->getQuery()->getSingleScalarResult();
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
            ->andWhere('s.statut NOT IN (:statutList)')->setParameter('statutList', SignalementStatus::excludedStatuses());

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
            ->setParameter('partner', $partner);

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
        $qb = $this->findForAPIQueryBuilder($user, true);
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

    public function findForAPIQueryBuilder(User $user, ?bool $includeCreatedByUser = false): QueryBuilder
    {
        $partners = $this->partnerAuthorizedResolver->resolveBy($user);
        $qb = $this->createQueryBuilder('s');

        $qb->select('DISTINCT s', 'territory')
            ->leftJoin('s.territory', 'territory')
            ->leftJoin('s.affectations', 'affectations');
        if ($includeCreatedByUser) {
            return $qb->where('affectations.partner IN (:partners) OR s.createdBy = :user')
                ->setParameter('partners', $partners)
                ->setParameter('user', $user);
        }

        return $qb->where('affectations.partner IN (:partners)')
            ->setParameter('partners', $partners);
    }

    /**
     * @return array<int, array<string, mixed>>
     *
     * @throws Exception
     * @throws Exception
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
     *
     * @throws Exception
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
        ?bool $compareNomOccupant = false,
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

        if ($compareNomOccupant && null !== $signalement->getNomOccupant()) {
            $qb->andWhere('s.nomOccupant = :nomOccupant')
                ->setParameter('nomOccupant', $signalement->getNomOccupant());
        }

        return $qb->getQuery()->getResult();
    }

    private function createSignalementQueryBuilder(
        User $user,
        ?SignalementStatus $signalementStatus = null,
        ?AffectationStatus $affectationStatus = null,
        ?bool $onlyWithoutSubscription = false,
        ?TabQueryParameters $tabQueryParameters = null,
    ): QueryBuilder {
        $qb = $this->createQueryBuilder('s');

        if (null !== $signalementStatus) {
            $qb
                ->andWhere('s.statut = :statut')
                ->setParameter('statut', $signalementStatus);
        }

        if ($tabQueryParameters?->territoireId) {
            $qb
                ->andWhere('s.territory = :territoireId')
                ->setParameter('territoireId', $tabQueryParameters->territoireId);
        } elseif (!$user->isSuperAdmin()) {
            $qb->andWhere('s.territory IN (:territories)')->setParameter('territories', $user->getPartnersTerritories());
        }

        if ($tabQueryParameters->createdFrom) {
            $qb->andWhere(
                TabDossier::CREATED_FROM_FORMULAIRE_USAGER === $tabQueryParameters->createdFrom
                    ? 's.createdBy IS NULL'
                    : 's.createdBy IS NOT NULL'
            );
        }

        if (!empty($tabQueryParameters->partenairesId)) {
            if (\in_array('AUCUN', $tabQueryParameters->partenairesId)) {
                $qb->leftJoin('s.affectations', 'a')->andWhere('a.partner IS NULL');
            } else {
                $qb
                    ->leftJoin('s.affectations', 'a')
                    ->andWhere('a.partner IN (:partenairesId)')
                    ->setParameter('partenairesId', $tabQueryParameters->partenairesId);
            }
        }

        if ($affectationStatus) {
            $qb->andWhere('a.statut = :affectationStatus');
            $qb->setParameter('affectationStatus', $affectationStatus);
        }

        if ($onlyWithoutSubscription) {
            $subquery = 'SELECT u FROM '.User::class.' u JOIN u.userPartners up JOIN up.partner p WHERE p IN (:partners)';
            $qb
                ->leftJoin('s.userSignalementSubscriptions', 'uss', 'WITH', 'uss.user IN ('.$subquery.')')
                ->andWhere('uss.id IS NULL')
                ->setParameter('partners', $user->getPartners());
        }

        return $qb;
    }

    /**
     * @return TabDossier[]
     */
    public function findNewDossiersFrom(
        ?SignalementStatus $signalementStatus = null,
        ?AffectationStatus $affectationStatus = null,
        ?TabQueryParameters $tabQueryParameters = null,
    ): array {
        /** @var User $user */
        $user = $this->security->getUser();

        $qb = $this->createSignalementQueryBuilder(
            user: $user,
            signalementStatus: $signalementStatus,
            affectationStatus: $affectationStatus,
            tabQueryParameters: $tabQueryParameters
        );

        if (TabDossier::CREATED_FROM_FORMULAIRE_PRO === $tabQueryParameters->createdFrom) {
            $qb
                ->leftJoin('s.createdBy', 'u')
                ->leftJoin('u.userPartners', 'up')
                ->leftJoin('up.partner', 'p');
        }

        $qb->select(
            \sprintf(
                'NEW %s(
                    s.uuid,
                    s.profileDeclarant,
                    s.nomOccupant,
                    s.prenomOccupant,
                    s.reference,
                    CONCAT_WS(\', \', s.adresseOccupant, CONCAT(s.cpOccupant, \' \', s.villeOccupant)),
                    s.createdAt,'.
                    (TabDossier::CREATED_FROM_FORMULAIRE_PRO === $tabQueryParameters->createdFrom
                        ? 'CONCAT(UPPER(u.nom), \' \', u.prenom), p.nom,'
                        : '\'\' , \'\' ,'
                    ).
                    'CASE
                        WHEN s.isLogementSocial = true THEN \'PUBLIC\'
                        ELSE \'PRIVÉ\'
                    END,
                    s.validatedAt
                )',
                TabDossier::class
            )
        );

        if (null !== $tabQueryParameters
            && in_array($tabQueryParameters->sortBy, ['createdAt', 'nomOccupant'], true)
            && in_array($tabQueryParameters->orderBy, ['ASC', 'DESC', 'asc', 'desc'], true)
        ) {
            $qb->orderBy('s.'.$tabQueryParameters->sortBy, $tabQueryParameters->orderBy);
        } else {
            $qb->orderBy('s.createdAt', 'DESC');
        }

        $qb->setMaxResults(TabDossier::MAX_ITEMS_LIST);

        return $qb->getQuery()->getResult();
    }

    /**
     * @throws NonUniqueResultException
     * @throws NoResultException
     */
    public function countNewDossiersFrom(
        ?SignalementStatus $signalementStatus = null,
        ?AffectationStatus $affectationStatus = null,
        ?TabQueryParameters $tabQueryParameters = null,
    ): int {
        /** @var User $user */
        $user = $this->security->getUser();

        $qb = $this->createSignalementQueryBuilder(
            user: $user,
            signalementStatus: $signalementStatus,
            affectationStatus: $affectationStatus,
            tabQueryParameters: $tabQueryParameters
        );

        $qb->select('COUNT(s.id)');

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * @return TabDossier[]
     */
    public function findDossiersNoAgentFrom(
        ?AffectationStatus $affectationStatus = null,
        ?TabQueryParameters $tabQueryParameters = null,
        bool $isLimitApplied = true,
    ): array {
        /** @var User $user */
        $user = $this->security->getUser();

        $qb = $this->createSignalementQueryBuilder(
            user: $user,
            affectationStatus: $affectationStatus,
            onlyWithoutSubscription: true,
            tabQueryParameters: $tabQueryParameters
        );

        $qb->select(
            \sprintf(
                'NEW %s(
                    s.uuid,
                    s.profileDeclarant,
                    s.nomOccupant,
                    s.prenomOccupant,
                    s.reference,
                    CONCAT_WS(\', \', s.adresseOccupant, CONCAT(s.cpOccupant, \' \', s.villeOccupant)),
                    s.createdAt,'.
                    '\'\' , \'\' ,
                    CASE
                        WHEN s.isLogementSocial = true THEN \'PUBLIC\'
                        ELSE \'PRIVÉ\'
                    END,
                    s.validatedAt
                )',
                TabDossier::class
            )
        );

        if (null !== $tabQueryParameters
            && in_array($tabQueryParameters->sortBy, ['createdAt', 'nomOccupant'], true)
            && in_array($tabQueryParameters->orderBy, ['ASC', 'DESC', 'asc', 'desc'], true)
        ) {
            $qb->orderBy('s.'.$tabQueryParameters->sortBy, $tabQueryParameters->orderBy);
        } else {
            $qb->orderBy('s.createdAt', 'DESC');
        }

        if ($isLimitApplied) {
            $qb->setMaxResults(TabDossier::MAX_ITEMS_LIST);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * @throws NonUniqueResultException
     * @throws NoResultException
     */
    public function countDossiersNoAgentFrom(
        ?AffectationStatus $affectationStatus = null,
        ?TabQueryParameters $tabQueryParameters = null,
    ): int {
        /** @var User $user */
        $user = $this->security->getUser();

        $qb = $this->createSignalementQueryBuilder(
            user: $user,
            affectationStatus: $affectationStatus,
            onlyWithoutSubscription: true,
            tabQueryParameters: $tabQueryParameters
        );

        $qb->select('COUNT(s.id)');

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * @param array<int, mixed> $territories
     *
     * @throws NonUniqueResultException
     * @throws NoResultException
     */
    public function countNouveauxDossiersKpi(array $territories = [], ?User $user = null): CountNouveauxDossiers
    {
        $select = sprintf(
            'NEW %s(
            %s, -- countFormulaireUsager
            %s, -- countFormulairePro
            %s, -- countSansAffectation
            %s, -- countNouveauxDossiers
            %s  -- countNoAgentDossiers
        )',
            CountNouveauxDossiers::class,
            $user ? 0 : 'COALESCE(SUM(CASE WHEN s.statut = :statut_validation AND s.createdBy IS NULL THEN 1 ELSE 0 END), 0)',
            $user ? 0 : 'COALESCE(SUM(CASE WHEN s.statut = :statut_validation AND s.createdBy IS NOT NULL THEN 1 ELSE 0 END), 0)',
            $user ? 0 : 'COALESCE(SUM(CASE WHEN s.statut = :statut_active AND a.id IS NULL THEN 1 ELSE 0 END), 0)',
            $user ? 'COALESCE(SUM(CASE WHEN a.partner IN (:partners) AND a.statut = :affectation_wait THEN 1 ELSE 0 END), 0)' : 0,
            $user ? 'COALESCE(SUM(CASE WHEN a.partner IN (:partners) AND a.statut = :affectation_accepted AND uss.id IS NULL THEN 1 ELSE 0 END), 0)' : 0,
        );

        $qb = $this
            ->createQueryBuilder('s')
            ->select($select)
            ->leftJoin('s.affectations', 'a');

        if (null === $user) {
            $qb->setParameter('statut_active', SignalementStatus::ACTIVE);
            $qb->setParameter('statut_validation', SignalementStatus::NEED_VALIDATION);
        }

        if (!empty($territories)) {
            $qb->andWhere('s.territory IN (:territories)')
                ->setParameter('territories', $territories);
        }

        if ($user?->isUserPartner() || $user?->isPartnerAdmin()) {
            $subquery = 'SELECT u FROM '.User::class.' u JOIN u.userPartners up JOIN up.partner p WHERE p IN (:partners)';
            $qb->setParameter('partners', $user->getPartners())
                ->setParameter('affectation_wait', AffectationStatus::WAIT)
                ->setParameter('affectation_accepted', AffectationStatus::ACCEPTED)
                ->leftJoin('s.userSignalementSubscriptions', 'uss', 'WITH', 'uss.user IN ('.$subquery.')');
        }

        return $qb->getQuery()->getSingleResult();
    }

    /**
     * @return TabDossier[]
     *
     * @throws \DateMalformedStringException
     */
    public function findDossiersFermePartenaireTous(?TabQueryParameters $tabQueryParameters): array
    {
        /** @var User $user */
        $user = $this->security->getUser();

        $qb = $this->createSignalementQueryBuilder(
            user: $user,
            signalementStatus: SignalementStatus::ACTIVE,
            tabQueryParameters: $tabQueryParameters
        );

        $qb->select("
            s.uuid,
            s.nomOccupant,
            s.prenomOccupant,
            s.reference,
            CONCAT_WS(', ', s.adresseOccupant, CONCAT(s.cpOccupant, ' ', s.villeOccupant)) AS fullAddress,
            MAX(a.answeredAt) AS lastClosedAt
        ")
            ->innerJoin('s.affectations', 'a')
            ->groupBy('s.uuid, s.nomOccupant, s.prenomOccupant, s.reference, s.adresseOccupant, s.cpOccupant, s.villeOccupant')
            ->having('COUNT(a.id) = SUM(CASE WHEN a.statut = :closed THEN 1 ELSE 0 END)')
            ->setParameter('closed', AffectationStatus::CLOSED);

        if (null !== $tabQueryParameters
            && 'closedAt' === $tabQueryParameters->sortBy
            && in_array($tabQueryParameters->orderBy, ['ASC', 'DESC', 'asc', 'desc'], true)
        ) {
            $qb->orderBy('MAX(a.answeredAt)', $tabQueryParameters->orderBy);
        } else {
            $qb->orderBy('MAX(a.answeredAt)', 'ASC');
        }

        $qb->setMaxResults(TabDossier::MAX_ITEMS_LIST);

        $rows = $qb->getQuery()->getArrayResult();

        return array_map(
            fn (array $row) => new TabDossier(
                uuid: $row['uuid'],
                nomDeclarant: $row['nomOccupant'],
                prenomDeclarant: $row['prenomOccupant'],
                reference: $row['reference'],
                adresse: $row['fullAddress'],
                clotureAt: $row['lastClosedAt'] ? new \DateTimeImmutable($row['lastClosedAt']) : null,
            ),
            $rows
        );
    }

    public function countDossiersFermePartenaireTous(?TabQueryParameters $tabQueryParameters): int
    {
        /** @var User $user */
        $user = $this->security->getUser();

        $qb = $this->createSignalementQueryBuilder(
            user: $user,
            signalementStatus: SignalementStatus::ACTIVE,
            tabQueryParameters: $tabQueryParameters
        );

        $em = $this->getEntityManager();

        $existsAtLeastOneAffectation = $em->createQueryBuilder()
            ->select('1')
            ->from(Affectation::class, 'a1')
            ->where('a1.signalement = s')
            ->getDQL();

        $existsAffectationNotClosed = $em->createQueryBuilder()
            ->select('1')
            ->from(Affectation::class, 'a2')
            ->where('a2.signalement = s')
            ->andWhere('a2.statut != :closed')
            ->getDQL();

        $qb->andWhere($qb->expr()->exists($existsAtLeastOneAffectation));
        $qb->andWhere($qb->expr()->not($qb->expr()->exists($existsAffectationNotClosed)));

        $qb->select('COUNT(DISTINCT s.id)');
        $qb->setParameter('closed', AffectationStatus::CLOSED);

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * @return TabDossier[]
     *
     * @throws \DateMalformedStringException
     */
    public function findDossiersDemandesFermetureByUsager(?TabQueryParameters $tabQueryParameters): array
    {
        /** @var User $user */
        $user = $this->security->getUser();

        $qb = $this->createSignalementQueryBuilder(
            user: $user,
            signalementStatus: SignalementStatus::ACTIVE,
            tabQueryParameters: $tabQueryParameters
        );

        $qb->select("
            s.uuid,
            s.nomOccupant,
            s.prenomOccupant,
            s.reference,
            CONCAT_WS(', ', s.adresseOccupant, CONCAT(s.cpOccupant, ' ', s.villeOccupant)) AS fullAddress,
            MAX(su.createdAt) AS demandeFermetureUsagerAt,
            DATEDIFF(CURRENT_DATE(), MAX(su.createdAt)) AS demandeFermetureUsagerDaysAgo,
            CASE WHEN s.isNotOccupant = 1 THEN 'TIERS DÉCLARANT' ELSE 'OCCUPANT' END AS demandeFermetureUsagerProfileDeclarant
        ")
            ->innerJoin('s.suivis', 'su', 'WITH', 'su.category = :suivi_category_abandon_procedure')
            ->andWhere('s.isUsagerAbandonProcedure = 1')
            ->groupBy('s.uuid, s.nomOccupant, s.prenomOccupant, s.reference, s.adresseOccupant, s.cpOccupant, s.villeOccupant, s.isNotOccupant')
            ->orderBy('MAX(su.createdAt)', 'DESC')
            ->setParameter('suivi_category_abandon_procedure', SuiviCategory::DEMANDE_ABANDON_PROCEDURE);

        if (null !== $tabQueryParameters
            && 'demandeFermetureUsagerAt' === $tabQueryParameters->sortBy
            && in_array($tabQueryParameters->orderBy, ['ASC', 'DESC', 'asc', 'desc'], true)
        ) {
            $qb->orderBy('MAX(su.createdAt)', $tabQueryParameters->orderBy);
        } else {
            $qb->orderBy('MAX(su.createdAt)', 'ASC');
        }

        $qb->setMaxResults($tabQueryParameters?->limit ?? TabDossier::MAX_ITEMS_LIST);

        $rows = $qb->getQuery()->getArrayResult();

        return array_map(
            fn (array $row) => new TabDossier(
                uuid: $row['uuid'],
                nomDeclarant: $row['nomOccupant'] ?? null,
                prenomDeclarant: $row['prenomOccupant'] ?? null,
                reference: $row['reference'] ?? null,
                adresse: $row['fullAddress'] ?? null,
                demandeFermetureUsagerDaysAgo: isset($row['demandeFermetureUsagerDaysAgo']) ? (int) $row['demandeFermetureUsagerDaysAgo'] : null,
                demandeFermetureUsagerProfileDeclarant: $row['demandeFermetureUsagerProfileDeclarant'] ?? null,
                demandeFermetureUsagerAt: null !== $row['demandeFermetureUsagerAt'] ? new \DateTimeImmutable($row['demandeFermetureUsagerAt']) : null
            ),
            $rows
        );
    }

    /**
     * @throws NonUniqueResultException
     * @throws NoResultException
     */
    public function countDossiersDemandesFermetureByUsager(?TabQueryParameters $tabQueryParameters): int
    {
        /** @var User $user */
        $user = $this->security->getUser();

        $qb = $this->createSignalementQueryBuilder(
            user: $user,
            signalementStatus: SignalementStatus::ACTIVE,
            tabQueryParameters: $tabQueryParameters
        );

        $qb
            ->select('COUNT(DISTINCT s.uuid)')
            ->innerJoin('s.suivis', 'su', 'WITH', 'su.category = :suivi_category_abandon_procedure')
            ->andWhere('s.isUsagerAbandonProcedure = 1')
            ->setParameter('suivi_category_abandon_procedure', SuiviCategory::DEMANDE_ABANDON_PROCEDURE);

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    private function getBaseSignalementsAvecRelancesSansReponseSql(): string
    {
        return <<<SQL
            FROM (
                SELECT
                    s.signalement_id,
                    MIN(s.created_at) AS first_relance_at,
                    COUNT(*) AS nb_relances
                FROM suivi s
                WHERE s.category = 'ASK_FEEDBACK_SENT'
                  AND EXISTS (
                    SELECT 1 FROM signalement si2
                    WHERE si2.id = s.signalement_id
                      AND si2.statut = 'ACTIVE'
                      AND (:territory_id IS NULL OR si2.territory_id = :territory_id)
                  )
                GROUP BY s.signalement_id
                HAVING COUNT(*) >= 3
            ) relances_usager
            INNER JOIN signalement si ON si.id = relances_usager.signalement_id
            INNER JOIN (
                SELECT
                    s.signalement_id,
                    MAX(s.created_at) AS shared_usager_at,
                    MAX(s.type) AS type
                FROM suivi s
                WHERE s.is_public = 1
                  AND EXISTS (
                    SELECT 1 FROM signalement si3
                    WHERE si3.id = s.signalement_id
                      AND si3.statut = 'ACTIVE'
                      AND (:territory_id IS NULL OR si3.territory_id = :territory_id)
                  )
                GROUP BY s.signalement_id
            ) last_usager_suivi ON last_usager_suivi.signalement_id = si.id
            WHERE
                si.statut = 'ACTIVE'
                AND NOT EXISTS (
                    SELECT 1
                    FROM suivi s2
                    WHERE s2.signalement_id = relances_usager.signalement_id
                      AND s2.type = 2
                      AND s2.created_at > relances_usager.first_relance_at
                )
                AND (:territory_id IS NULL OR si.territory_id = :territory_id)
        SQL;
    }

    /**
     * @return TabDossier[]
     *
     * @throws \DateMalformedStringException
     * @throws Exception
     */
    public function findSignalementsAvecRelancesSansReponse(TabQueryParameters $tabQueryParameters): array
    {
        $conn = $this->getEntityManager()->getConnection();

        $sql = <<<SQL
            SELECT
                si.uuid,
                si.id,
                si.reference,
                si.nom_occupant,
                si.prenom_occupant,
                CONCAT_WS(', ', si.adresse_occupant, CONCAT(si.cp_occupant, ' ', si.ville_occupant)) AS fullAddress,
                relances_usager.nb_relances,
                relances_usager.first_relance_at,
                last_usager_suivi.shared_usager_at AS last_suivi_shared_usager_at,
                last_usager_suivi.type AS last_suivi_type
        SQL;
        $sql .= $this->getBaseSignalementsAvecRelancesSansReponseSql();

        if ('ASC' === $tabQueryParameters->orderBy && 'nbRelanceFeedbackUsager' === $tabQueryParameters->sortBy) {
            $sql .= ' ORDER BY relances_usager.nb_relances ASC, relances_usager.first_relance_at DESC LIMIT 5';
        } else {
            $sql .= ' ORDER BY relances_usager.nb_relances DESC, relances_usager.first_relance_at DESC LIMIT 5';
        }

        $rows = $conn->executeQuery($sql, ['territory_id' => $tabQueryParameters->territoireId])->fetchAllAssociative();

        return array_map(/**
         * @throws \DateMalformedStringException
         */ function (array $row): TabDossier {
            return new TabDossier(
                uuid: $row['uuid'],
                nomDeclarant: $row['nom_occupant'],
                prenomDeclarant: $row['prenom_occupant'],
                reference: $row['reference'],
                adresse: $row['fullAddress'],
                nbRelanceDossier: (int) $row['nb_relances'],
                premiereRelanceDossierAt: new \DateTimeImmutable($row['first_relance_at']),
                dernierSuiviPublicAt: new \DateTimeImmutable($row['last_suivi_shared_usager_at']),
                dernierTypeSuivi: (string) $row['last_suivi_type'],
            );
        }, $rows);
    }

    public function countSignalementsAvecRelancesSansReponse(TabQueryParameters $tabQueryParameters): int
    {
        $conn = $this->getEntityManager()->getConnection();

        $sql = 'SELECT COUNT(*) FROM (SELECT relances_usager.signalement_id '.$this->getBaseSignalementsAvecRelancesSansReponseSql().') AS signalements_count';

        return (int) $conn->executeQuery($sql, ['territory_id' => $tabQueryParameters->territoireId])->fetchOne();
    }

    /**
     * @return int[]
     */
    public function getSignalementsIdAvecRelancesSansReponse(): array
    {
        $conn = $this->getEntityManager()->getConnection();

        $sql = 'SELECT si.id '.$this->getBaseSignalementsAvecRelancesSansReponseSql();

        return array_map('intval', $conn->executeQuery($sql, ['territory_id' => null])->fetchFirstColumn());
    }

    public function countAllDossiersAferme(User $user, ?TabQueryParameters $params): CountAfermer
    {
        return new CountAfermer(
            countDemandesFermetureByUsager: $this->countDossiersDemandesFermetureByUsager($params),
            countDossiersRelanceSansReponse: $this->countSignalementsAvecRelancesSansReponse($params),
            countDossiersFermePartenaireTous: $this->countDossiersFermePartenaireTous($params)
        );
    }

    /**
     * @return array<int, string|array<string, string>>
     */
    private function getBaseSignalementsSansSuiviPartenaireDepuis60JSql(
        User $user,
        TabQueryParameters $params,
        ?bool $withJoins = false,
    ): array {
        /** @var SuiviRepository $suiviRepository */
        $suiviRepository = $this->_em->getRepository(Suivi::class);
        $excludedIds = array_merge(
            $this->getSignalementsIdAvecRelancesSansReponse(),
            $suiviRepository->getSignalementsIdWithSuivisUsagerOrPoursuiteWithAskFeedbackBefore($user, $params)
        );
        $categories = [
            'MESSAGE_PARTNER',
            'SIGNALEMENT_EDITED_BO',
            'SIGNALEMENT_IS_ACTIVE',
            'SIGNALEMENT_IS_REOPENED',
            'INTERVENTION_IS_CREATED',
            'INTERVENTION_IS_CANCELED',
            'INTERVENTION_IS_ABORTED',
            'INTERVENTION_HAS_CONCLUSION',
            'INTERVENTION_HAS_CONCLUSION_EDITED',
            'INTERVENTION_IS_RESCHEDULED',
            'NEW_DOCUMENT',
        ];

        $paramsToBind = [];
        $types = [];
        $categoryList = "'".implode("','", $categories)."'";
        $sql = <<<SQL
            FROM signalement si
            INNER JOIN suivi s ON s.signalement_id = si.id
        SQL;
        if ($withJoins) {
            $sql .= <<<SQL
                LEFT JOIN user u ON u.id = s.created_by_id
                LEFT JOIN user_partner up ON up.user_id = u.id
                LEFT JOIN partner p ON p.id = up.partner_id
            SQL;
        }
        if ($user->isPartnerAdmin() || $user->isUserPartner() || ($params->partners && count($params->partners) > 0)) {
            $sql .= <<<SQL
                LEFT JOIN affectation aff ON aff.signalement_id = si.id
            SQL;
        }

        $sql .= <<<SQL
            WHERE s.category IN ($categoryList)
            AND s.created_at = (
                SELECT MAX(s2.created_at)
                FROM suivi s2
                WHERE s2.signalement_id = si.id
                AND s2.category IN ($categoryList)
            )
            AND s.created_at < :dateLimit
            AND si.statut = 'ACTIVE'
        SQL;

        if ($user->isPartnerAdmin() || $user->isUserPartner()) {
            $sql .= ' AND aff.partner_id IN (:partners)';
            $paramsToBind['partners'] = array_map(
                fn ($partner) => $partner->getId(),
                $user->getPartners()->toArray()
            );
            $types['partners'] = ArrayParameterType::INTEGER;
        }

        if ($params->territoireId) {
            $sql .= ' AND si.territory_id = '.$params->territoireId;
        } elseif (!$user->isSuperAdmin()) {
            $sql .= ' AND si.territory_id IN ('.implode(',', array_keys($user->getPartnersTerritories())).')';
        }

        if ($params->mesDossiersAverifier && '1' === $params->mesDossiersAverifier) {
            $sql .= ' AND EXISTS (
            SELECT 1
            FROM user_signalement_subscription uss
            WHERE uss.signalement_id = si.id
              AND uss.user_id = '.$user->getId().'
        )';
        }

        $paramsToBind['dateLimit'] = (new \DateTimeImmutable('-60 days'))->format('Y-m-d H:i:s');

        if ($params->partners && count($params->partners) > 0) {
            $sql .= ' AND aff.partner_id IN (:partnersId)';
            $paramsToBind['partnersId'] = $params->partners;
            $types['partnersId'] = ArrayParameterType::INTEGER;
        }

        if ($params->queryCommune) {
            $listCity = [$params->queryCommune];
            if (isset(CommuneHelper::COMMUNES_ARRONDISSEMENTS[$params->queryCommune])) {
                $listCity = array_merge($listCity, CommuneHelper::COMMUNES_ARRONDISSEMENTS[$params->queryCommune]);
            }
            $sql .= ' AND (si.cp_occupant IN (:cities) OR si.ville_occupant IN (:cities))';
            $paramsToBind['cities'] = $listCity;
            $types['cities'] = ArrayParameterType::STRING;
        }

        if (!empty($excludedIds)) {
            $sql .= ' AND si.id NOT IN (:excludedIds)';
            $paramsToBind['excludedIds'] = $excludedIds;
            $types['excludedIds'] = ArrayParameterType::INTEGER;
        }

        return [$sql, $paramsToBind, $types];
    }

    public function countSignalementsSansSuiviPartenaireDepuis60Jours(User $user, ?TabQueryParameters $params): int
    {
        $conn = $this->getEntityManager()->getConnection();

        $sql = 'SELECT COUNT(DISTINCT si.id) ';
        [$sqlPrincipal, $paramsToBind, $types] = $this->getBaseSignalementsSansSuiviPartenaireDepuis60JSql($user, $params);
        $sql .= $sqlPrincipal;

        return (int) $conn->executeQuery($sql, $paramsToBind, $types)->fetchOne();
    }

    /**
     * @return int[]
     */
    public function getSignalementsIdSansSuiviPartenaireDepuis60Jours(User $user, TabQueryParameters $params): array
    {
        $conn = $this->getEntityManager()->getConnection();

        $sql = 'SELECT DISTINCT si.id ';
        [$sqlPrincipal, $paramsToBind, $types] = $this->getBaseSignalementsSansSuiviPartenaireDepuis60JSql($user, $params);
        $sql .= $sqlPrincipal;

        return array_map('intval', $conn->executeQuery($sql, $paramsToBind, $types)->fetchFirstColumn());
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function findSignalementsSansSuiviPartenaireDepuis60Jours(User $user, ?TabQueryParameters $params): array
    {
        $conn = $this->getEntityManager()->getConnection();

        $sql = <<<SQL
        SELECT
            si.id,
            si.uuid AS uuid,
            si.reference AS reference,
            si.nom_occupant AS nomOccupant,
            si.prenom_occupant AS prenomOccupant,
            CONCAT_WS(', ', si.adresse_occupant, CONCAT(si.cp_occupant, ' ', si.ville_occupant)) AS adresse,
            MAX(s.created_at) AS dernierSuiviAt,
            DATEDIFF(CURRENT_DATE(), MAX(s.created_at)) AS nbJoursDepuisDernierSuivi,
            MAX(s.category) AS suiviCategory,
            MAX(p.nom) AS derniereActionPartenaireNom,
            MAX(u.nom) AS derniereActionPartenaireNomAgent,
            MAX(u.prenom) AS derniereActionPartenairePrenomAgent
        SQL;
        [$sqlPrincipal, $paramsToBind, $types] = $this->getBaseSignalementsSansSuiviPartenaireDepuis60JSql($user, $params, true);
        $sql .= $sqlPrincipal;
        $sql .= ' GROUP BY si.id, si.uuid, si.reference, si.nom_occupant, si.prenom_occupant, si.adresse_occupant, si.cp_occupant, si.ville_occupant';

        if ($params && in_array($params->sortBy, ['createdAt'], true)
            && in_array($params->orderBy, ['ASC', 'DESC', 'asc', 'desc'], true)
        ) {
            $sql .= ' ORDER BY MAX(s.created_at) '.$params->orderBy;
        } else {
            $sql .= ' ORDER BY MAX(s.created_at) ASC';
        }

        $sql .= ' LIMIT '.TabDossier::MAX_ITEMS_LIST;

        return $conn->executeQuery($sql, $paramsToBind, $types)->fetchAllAssociative();
    }

    /**
     * @return string[]
     */
    public function getSignalementsUuidSansAgent(TabQueryParameters $params): array
    {
        /** @var User $user */
        $user = $this->security->getUser();
        if (null === $params->territoireId) {
            $params->partenairesId = $user->getPartners()
                ->map(fn ($partner) => $partner->getId())
                ->toArray();
        }

        $signalements = $this->findDossiersNoAgentFrom(AffectationStatus::ACCEPTED, $params, false);

        return array_map(fn (TabDossier $dossier) => $dossier->uuid, $signalements);
    }

    public function trimFields(): void
    {
        $connection = $this->getEntityManager()->getConnection();
        // Replace unbreakable spaces, and then trim
        $sql = 'UPDATE signalement SET
            mail_occupant = TRIM(REPLACE(mail_occupant, UNHEX("C2A0"), " ")),
            prenom_occupant = TRIM(REPLACE(prenom_occupant, UNHEX("C2A0"), " ")),
            nom_occupant = TRIM(REPLACE(nom_occupant, UNHEX("C2A0"), " ")),
            mail_declarant = TRIM(REPLACE(mail_declarant, UNHEX("C2A0"), " ")),
            prenom_declarant = TRIM(REPLACE(prenom_declarant, UNHEX("C2A0"), " ")),
            nom_declarant = TRIM(REPLACE(nom_declarant, UNHEX("C2A0"), " ")),
            mail_proprio = TRIM(REPLACE(mail_proprio, UNHEX("C2A0"), " ")),
            prenom_proprio = TRIM(REPLACE(prenom_proprio, UNHEX("C2A0"), " ")),
            nom_proprio = TRIM(REPLACE(nom_proprio, UNHEX("C2A0"), " "))';

        $connection->prepare($sql)->executeStatement();
    }

    private function buildBaseQbForNonDeliverable(User $user, ?TabQueryParameters $params): QueryBuilder
    {
        $qb = $this->_em->createQueryBuilder();

        $qb->from(Signalement::class, 's')
            ->innerJoin(
                EmailDeliveryIssue::class,
                'edi',
                'WITH',
                $qb->expr()->orX(
                    $qb->expr()->eq('s.mailOccupant', 'edi.email'),
                    $qb->expr()->eq('s.mailDeclarant', 'edi.email')
                )
            )
            ->leftJoin('s.affectations', 'aff')
            ->where('s.statut IN (:statutList)')
            ->setParameter('statutList', [SignalementStatus::NEED_VALIDATION, SignalementStatus::ACTIVE]);

        if ($user->isPartnerAdmin() || $user->isUserPartner()) {
            $existsAffectation = $this->_em->createQueryBuilder()
                ->select('1')
                ->from(Affectation::class, 'af')
                ->where('af.signalement = s')
                ->andWhere('af.partner IN (:partners)')
                ->andWhere('af.statut = :affectationStatus')
                ->getDQL();
            $qb->andWhere($qb->expr()->exists($existsAffectation))
                ->setParameter('partners', $user->getPartners())
                ->setParameter('affectationStatus', AffectationStatus::ACCEPTED);
        }

        if ($params?->territoireId) {
            $qb
                ->andWhere('s.territory = :territoireId')
                ->setParameter('territoireId', $params->territoireId);
        } elseif (!$user->isSuperAdmin()) {
            $qb
                ->andWhere('s.territory IN (:territories)')
                ->setParameter('territories', $user->getPartnersTerritories());
        }

        if ($params && $params->mesDossiersAverifier && '1' === $params->mesDossiersAverifier) {
            $existsSubscription = $this->_em->createQueryBuilder()
                ->select('1')
                ->from(UserSignalementSubscription::class, 'uss')
                ->where('uss.signalement = s')
                ->andWhere('uss.user = :currentUser')
                ->getDQL();
            $qb->andWhere($qb->expr()->exists($existsSubscription))
                ->setParameter('currentUser', $user);
        }

        if ($params && $params->queryCommune) {
            $query = '%'.$params->queryCommune.'%';
            $qb
                ->andWhere(
                    $qb->expr()->orX(
                        $qb->expr()->like('s.cpOccupant', ':query'),
                        $qb->expr()->like('s.villeOccupant', ':query')
                    )
                )
                ->setParameter('query', '%'.$query.'%');
        }

        if ($params && $params->partners && count($params->partners) > 0) {
            $qb->andWhere('aff.partner IN (:partnersId)')
                ->setParameter('partnersId', $params->partners);
        }

        return $qb;
    }

    /**
     * @return Signalement[]
     */
    public function findActiveSignalementsWithInvalidEmails(User $user, ?TabQueryParameters $params): array
    {
        $qb = $this->buildBaseQbForNonDeliverable($user, $params);

        $qb->select(
            's.uuid AS uuid',
            's.nomOccupant AS nomOccupant',
            's.prenomOccupant AS prenomOccupant',
            's.reference AS reference',
            "CONCAT_WS(', ', s.adresseOccupant, CONCAT(s.cpOccupant, ' ', s.villeOccupant)) AS adresse",
            's.createdAt AS createdAt',
            's.lastSuiviAt AS dernierSuiviAt',
            's.lastSuiviBy AS derniereActionPartenaireNom',
            "CASE
                WHEN (FIND_IN_SET(s.mailOccupant, GROUP_CONCAT(DISTINCT edi.email)) > 0
                    AND FIND_IN_SET(s.mailDeclarant, GROUP_CONCAT(DISTINCT edi.email)) > 0)
                    THEN 'Occupant et Tiers'
                WHEN FIND_IN_SET(s.mailOccupant, GROUP_CONCAT(DISTINCT edi.email)) > 0
                    THEN 'Occupant'
                WHEN FIND_IN_SET(s.mailDeclarant, GROUP_CONCAT(DISTINCT edi.email)) > 0
                    THEN 'Tiers'
                ELSE ''
            END AS profilNonDeliverable"
        );

        if ($params && in_array($params->sortBy, ['createdAt', 'nomOccupant'], true)
            && in_array($params->orderBy, ['ASC', 'DESC', 'asc', 'desc'], true)
        ) {
            $qb->orderBy($params->sortBy, $params->orderBy);
        } else {
            $qb->orderBy('createdAt', 'DESC');
        }

        $qb->setMaxResults(TabDossier::MAX_ITEMS_LIST);
        $qb->groupBy('s.id');

        return $qb->getQuery()->getArrayResult();
    }

    /**
     * @return array<int>
     */
    public function findIdsNonDeliverableSignalements(User $user, ?TabQueryParameters $params): array
    {
        $qb = $this->buildBaseQbForNonDeliverable($user, $params);
        $qb->select('s.id')->groupBy('s.id');

        return $qb->getQuery()->getSingleColumnResult();
    }

    public function countNonDeliverableSignalements(User $user, ?TabQueryParameters $params): int
    {
        $qb = $this->buildBaseQbForNonDeliverable($user, $params);
        $qb->select('COUNT(DISTINCT s.id)');

        return (int) $qb->getQuery()->getSingleScalarResult();
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

    public function findOneForLoginBailleur(string $reference, string $loginBailleur): ?Signalement
    {
        return $this->createQueryBuilder('s')
            ->leftJoin('s.suivis', 'su')
            ->where('s.reference = :reference')
            ->setParameter('reference', $reference)
            ->andWhere('s.loginBailleur = :loginBailleur')
            ->setParameter('loginBailleur', $loginBailleur)
            ->andWhere('s.statut = :statutInjonction OR su.category IN (:injonctionCategories)')
            ->setParameter('statutInjonction', SignalementStatus::INJONCTION_BAILLEUR)
            ->setParameter('injonctionCategories', SuiviCategory::injonctionBailleurCategories())
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function countInjonctionsAvecAide(
        User $user,
        ?TabQueryParameters $params,
    ): int {
        $qb = $this->createQueryBuilder('s')
            ->where('s.statut = :statut')
            ->setParameter('statut', SignalementStatus::INJONCTION_BAILLEUR)
            ->innerJoin('s.suivis', 'su')
            ->andWhere('su.category = :aideCategory')
            ->setParameter('aideCategory', SuiviCategory::INJONCTION_BAILLEUR_REPONSE_OUI_AVEC_AIDE);

        $qb->select('COUNT(s.id)');

        if ($params?->territoireId) {
            $qb
                ->andWhere('s.territory = :territoireId')
                ->setParameter('territoireId', $params->territoireId);
        } elseif (!$user->isSuperAdmin()) {
            $qb
                ->andWhere('s.territory IN (:territories)')
                ->setParameter('territories', $user->getPartnersTerritories());
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    public function findInjonctionFilteredPaginated(
        SearchSignalementInjonction $searchSignalementInjonction,
        int $maxResult,
    ): Paginator {
        $queryBuilder = $this->createQueryBuilder('s')
            ->where('s.statut = :statut')
            ->setParameter('statut', SignalementStatus::INJONCTION_BAILLEUR);

        if (!empty($searchSignalementInjonction->getTerritoire())) {
            $queryBuilder->andWhere('s.territory = :territory')->setParameter('territory', $searchSignalementInjonction->getTerritoire());
        }

        if (!empty($searchSignalementInjonction->getInjonctionAvecAide())) {
            if ('oui' === $searchSignalementInjonction->getInjonctionAvecAide()) {
                $queryBuilder->andWhere(
                    $queryBuilder->expr()->exists(
                        $this->createQueryBuilder('s2')
                            ->select('1')
                            ->join('s2.suivis', 'su2')
                            ->where('s2 = s')
                            ->andWhere('su2.category = :aideCategory')
                            ->getDQL()
                    )
                );
            } else {
                $queryBuilder->andWhere(
                    $queryBuilder->expr()->not(
                        $queryBuilder->expr()->exists(
                            $this->createQueryBuilder('s3')
                                ->select('1')
                                ->join('s3.suivis', 'su3')
                                ->where('s3 = s')
                                ->andWhere('su3.category = :aideCategory')
                                ->getDQL()
                        )
                    )
                );
            }

            $queryBuilder->setParameter('aideCategory', SuiviCategory::INJONCTION_BAILLEUR_REPONSE_OUI_AVEC_AIDE);
        }

        if (!empty($searchSignalementInjonction->getOrderType())) {
            [$orderField, $orderDirection] = explode('-', $searchSignalementInjonction->getOrderType());
            $queryBuilder->orderBy($orderField, $orderDirection);
        } else {
            $queryBuilder->orderBy('s.id', 'DESC');
        }

        $firstResult = ($searchSignalementInjonction->getPage() - 1) * $maxResult;
        $queryBuilder->setFirstResult($firstResult)->setMaxResults($maxResult);

        $paginator = new Paginator($queryBuilder->getQuery());

        return $paginator;
    }
}
