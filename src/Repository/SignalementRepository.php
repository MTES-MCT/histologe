<?php

namespace App\Repository;

use App\Dto\StatisticsFilters;
use App\Entity\Partner;
use App\Entity\Signalement;
use App\Entity\Territory;
use App\Entity\User;
use App\Service\SearchFilterService;
use App\Service\Statistics\CriticitePercentStatisticProvider;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @method Signalement|null find($id, $lockMode = null, $lockVersion = null)
 * @method Signalement|null findOneBy(array $criteria, array $orderBy = null)
 * @method Signalement[]    findAll()
 * @method Signalement[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SignalementRepository extends ServiceEntityRepository
{
    public const ARRAY_LIST_PAGE_SIZE = 30;
    public const MARKERS_PAGE_SIZE = 9000; // @todo: is high cause duplicate result, the query findAllWithGeoData should be reviewed

    private SearchFilterService $searchFilterService;

    public function __construct(ManagerRegistry $registry, private EntityManagerInterface $entityManager)
    {
        parent::__construct($registry, Signalement::class);
        $this->searchFilterService = new SearchFilterService();
    }

    public function findAllWithGeoData($user, $options, int $offset, Territory|null $territory): array
    {
        $firstResult = $offset;
        $qb = $this->createQueryBuilder('s');
        $qb->select('PARTIAL s.{id,details,uuid,reference,nomOccupant,prenomOccupant,adresseOccupant,cpOccupant,villeOccupant,scoreCreation,statut,createdAt,geoloc,territory},
            PARTIAL a.{id,partner,createdAt},
            PARTIAL criteres.{id,label},
            PARTIAL partner.{id,nom}');

        $qb->leftJoin('s.affectations', 'a')
            ->leftJoin('s.criteres', 'criteres')
            ->leftJoin('a.partner', 'partner');
        if ($user) {
            $qb->andWhere('partner = :partner')->setParameter('partner', $user->getPartner());
        }
        if ($territory) {
            $qb->andWhere('s.territory = :territory')->setParameter('territory', $territory);
        }

        $qb = $this->searchFilterService->applyFilters($qb, $options);
        $qb->addSelect('a', 'partner', 'criteres');

        return $qb->andWhere("JSON_EXTRACT(s.geoloc,'$.lat') != ''")
            ->andWhere("JSON_EXTRACT(s.geoloc,'$.lng') != ''")
            ->andWhere('s.statut != 7')
            ->setFirstResult($firstResult)
            ->setMaxResults(self::MARKERS_PAGE_SIZE)
            ->getQuery()->getArrayResult();
    }

    public function findAllWithAffectations($year): array
    {
        return $this->createQueryBuilder('s')
            ->where('s.statut != 7')
            ->andWhere('YEAR(s.createdAt) = '.$year)
            ->leftJoin('s.affectations', 'affectations')
            ->addSelect('affectations', 's')
            ->getQuery()
            ->getResult();
    }

    public function countAll(Territory|null $territory, bool $removeImported = false, bool $removeArchived = false): int
    {
        $qb = $this->createQueryBuilder('s');
        $qb->select('COUNT(s.id)');

        if ($removeArchived) {
            $qb->andWhere('s.statut != :statutArchived')
                ->setParameter('statutArchived', Signalement::STATUS_ARCHIVED);
        }

        if ($removeImported) {
            $qb->andWhere('s.isImported IS NULL OR s.isImported = 0');
        }
        if ($territory) {
            $qb->andWhere('s.territory = :territory')->setParameter('territory', $territory);
        }

        return $qb->getQuery()
            ->getSingleScalarResult();
    }

    public function countImported(): int
    {
        $qb = $this->createQueryBuilder('s');
        $qb->select('COUNT(s.id)');
        $qb->andWhere('s.statut != :statutArchived')
            ->setParameter('statutArchived', Signalement::STATUS_ARCHIVED);
        $qb->andWhere('s.isImported = 1');

        return $qb->getQuery()
            ->getSingleScalarResult();
    }

    public function countByStatus(Territory|null $territory, int|null $year = null, bool $removeImported = false): array
    {
        $qb = $this->createQueryBuilder('s');
        $qb->select('COUNT(s.id) as count')
            ->addSelect('s.statut')
            ->where('s.statut != :statutArchived')
            ->setParameter('statutArchived', Signalement::STATUS_ARCHIVED);

        if ($removeImported) {
            $qb->andWhere('s.isImported IS NULL OR s.isImported = 0');
        }

        if ($territory) {
            $qb->andWhere('s.territory = :territory')->setParameter('territory', $territory);
        }
        if ($year) {
            $qb->andWhere('YEAR(s.createdAt) = :year')->setParameter('year', $year);
        }

        $qb->indexBy('s', 's.statut')
            ->groupBy('s.statut');

        return $qb->getQuery()
            ->getResult();
    }

    public function countValidated(bool $removeImported = false): int
    {
        $notStatus = [Signalement::STATUS_NEED_VALIDATION, Signalement::STATUS_REFUSED, Signalement::STATUS_ARCHIVED];
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
            ->setParameter('closedStatus', Signalement::STATUS_CLOSED);

        if ($removeImported) {
            $qb->andWhere('s.isImported IS NULL OR s.isImported = 0');
        }

        return $qb->getQuery()
            ->getSingleScalarResult();
    }

    public function countByTerritory(bool $removeImported = false): array
    {
        $qb = $this->createQueryBuilder('s');
        $qb->select('COUNT(s.id) AS count, t.zip, t.name, t.id')
            ->leftJoin('s.territory', 't')

            ->where('s.statut != :statutArchived')
            ->setParameter('statutArchived', Signalement::STATUS_ARCHIVED);

        if ($removeImported) {
            $qb->andWhere('s.isImported IS NULL OR s.isImported = 0');
        }

        $qb->groupBy('t.id');

        return $qb->getQuery()
            ->getResult();
    }

    public function countByMonth(Territory|null $territory, int|null $year, bool $removeImported = false): array
    {
        $qb = $this->createQueryBuilder('s');
        $qb->select('COUNT(s.id) AS count, MONTH(s.createdAt) AS month, YEAR(s.createdAt) AS year')

            ->where('s.statut != :statutArchived')
            ->setParameter('statutArchived', Signalement::STATUS_ARCHIVED);

        if ($removeImported) {
            $qb->andWhere('s.isImported IS NULL OR s.isImported = 0');
        }

        if ($territory) {
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

    public function countBySituation(Territory|null $territory, int|null $year, bool $removeImported = false): array
    {
        $qb = $this->createQueryBuilder('s');
        $qb->select('COUNT(s.id) AS count, sit.id, sit.menuLabel')
            ->leftJoin('s.situations', 'sit')

            ->where('s.statut != :statutArchived')
            ->setParameter('statutArchived', Signalement::STATUS_ARCHIVED);

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

    public function countByMotifCloture(Territory|null $territory, int|null $year, bool $removeImported = false): array
    {
        $qb = $this->createQueryBuilder('s');
        $qb->select('COUNT(s.id) AS count, s.motifCloture')

            ->where('s.motifCloture IS NOT NULL')
            ->andWhere('s.motifCloture != \'0\'')
            ->andWhere('s.closedAt IS NOT NULL')

            ->andWhere('s.statut != :statutArchived')
            ->setParameter('statutArchived', Signalement::STATUS_ARCHIVED);

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

        return $qb->getQuery()
            ->getResult();
    }

    public function findOneOpenedByMailOccupant(string $email): ?Signalement
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.mailOccupant = :email')
            ->setParameter('email', $email)
            ->andWhere('s.statut != :status')
            ->setParameter('status', Signalement::STATUS_ARCHIVED)
            ->andWhere('s.statut != :status2')
            ->setParameter('status2', Signalement::STATUS_CLOSED)
            ->andWhere('s.statut != :status3')
            ->setParameter('status3', Signalement::STATUS_REFUSED)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findOneOpenedByMailDeclarant(string $email): ?Signalement
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.mailDeclarant = :email')
            ->setParameter('email', $email)
            ->andWhere('s.statut != :status')
            ->setParameter('status', Signalement::STATUS_ARCHIVED)
            ->andWhere('s.statut != :status2')
            ->setParameter('status2', Signalement::STATUS_CLOSED)
            ->andWhere('s.statut != :status3')
            ->setParameter('status3', Signalement::STATUS_REFUSED)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findByStatusAndOrCityForUser(User|UserInterface $user = null, array $options, int|null $export): array|Paginator
    {
        $pageSize = $export ?? self::ARRAY_LIST_PAGE_SIZE;
        $firstResult = (($options['page'] ?? 1) - 1) * $pageSize;
        $qb = $this->createQueryBuilder('s');
        $qb->where('s.statut != :status')
            ->setParameter('status', Signalement::STATUS_ARCHIVED);
        if (!$export) {
            $qb->select('PARTIAL s.{id,uuid,reference,isNotOccupant, nomOccupant,prenomOccupant,adresseOccupant,cpOccupant,villeOccupant,mailOccupant, scoreCreation,statut,createdAt,geoloc}');
            $qb->leftJoin('s.affectations', 'affectations');
            $qb->leftJoin('s.tags', 'tags');
            $qb->leftJoin('affectations.partner', 'partner');
            $qb->leftJoin('s.suivis', 'suivis');
            $qb->leftJoin('s.criteres', 'criteres');
            $qb->addSelect('affectations', 'partner', 'suivis');
        }
        if (!$user->isSuperAdmin()) {
            $qb->andWhere('s.territory = :territory')->setParameter('territory', $user->getTerritory());
        }
        $qb = $this->searchFilterService->applyFilters($qb, $options);
        $qb->orderBy('s.createdAt', 'DESC');
        if (!$export) {
            $qb->setFirstResult($firstResult)
                ->setMaxResults($pageSize);
            $qb->getQuery();

            return new Paginator($qb, true);
        }

        return $qb->getQuery()->getResult();
    }

    public function findCities(User|UserInterface|null $user, Territory|null $territory): array|int|string
    {
        $qb = $this->createQueryBuilder('s')
            ->select('s.villeOccupant city')
            ->where('s.statut != :status')
            ->setParameter('status', Signalement::STATUS_ARCHIVED);
        if ($user) {
            $qb->leftJoin('s.affectations', 'affectations')
                ->leftJoin('affectations.partner', 'partner')
                ->andWhere('partner = :partner')
                ->setParameter('partner', $user->getPartner());
        }
        if ($territory) {
            $qb->andWhere('s.territory = :territory')
                ->setParameter('territory', $territory);
        }

        return $qb->groupBy('s.villeOccupant')
            ->getQuery()
            ->getResult();
    }

    public function findOneByCodeForPublic($code): ?Signalement
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.codeSuivi = :code')
            ->setParameter('code', $code)
            ->leftJoin('s.suivis', 'suivis', Join::WITH, 'suivis.isPublic = 1')
            ->addSelect('suivis')
            ->getQuery()
            ->getOneOrNullResult();
    }

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

        return $queryBuilder->getQuery()->getOneOrNullResult();
    }

    public function findUsersPartnerEmailAffectedToSignalement(int $signalementId, ?Partner $partnerToExclude = null): array
    {
        $queryBuilder = $this->createQueryBuilder('s');
        $queryBuilder
            ->select('u.email')
            ->innerJoin('s.affectations', 'a')
            ->innerJoin('a.partner', 'p')
            ->innerJoin('p.users', 'u')
            ->where('s.id = :signalement_id')
            ->setParameter('signalement_id', $signalementId)
            ->andWhere('u.statut = '.User::STATUS_ACTIVE)
            ->andWhere('u.isMailingActive = true');

        if (null !== $partnerToExclude) {
            $queryBuilder
                ->andWhere('a.partner != :partner')
                ->setParameter('partner', $partnerToExclude);
        }

        $usersEmail = array_map(function ($value) {
            return $value['email'];
        }, $queryBuilder->getQuery()->getArrayResult());

        return $usersEmail;
    }

    public function findPartnersEmailAffectedToSignalement(int $signalementId): array
    {
        $queryBuilder = $this->createQueryBuilder('s');
        $queryBuilder
            ->select('p.email')
            ->innerJoin('s.affectations', 'a')
            ->innerJoin('a.partner', 'p')
            ->where('s.id = :signalement_id')
            ->setParameter('signalement_id', $signalementId);

        $partnersEmail = array_map(function ($value) {
            return $value['email'];
        }, $queryBuilder->getQuery()->getArrayResult());

        return $partnersEmail;
    }

    public function getAverageCriticite(Territory|null $territory, bool $removeImported = false): ?float
    {
        $qb = $this->createQueryBuilder('s');
        $qb->select('AVG(s.scoreCreation)');

        if ($removeImported) {
            $qb->andWhere('s.isImported IS NULL OR s.isImported = 0');
        }
        if ($territory) {
            $qb->andWhere('s.territory = :territory')->setParameter('territory', $territory);
        }

        return $qb->getQuery()
            ->getSingleScalarResult();
    }

    public function getAverageDaysValidation(Territory|null $territory, bool $removeImported = false): ?float
    {
        return $this->getAverageDayResult('validatedAt', $territory, $removeImported);
    }

    public function getAverageDaysClosure(Territory|null $territory, bool $removeImported = false): ?float
    {
        return $this->getAverageDayResult('closedAt', $territory, $removeImported);
    }

    private function getAverageDayResult(string $field, Territory|null $territory, bool $removeImported = false): ?float
    {
        $qb = $this->createQueryBuilder('s');
        $qb->select('AVG(datediff(s.'.$field.', s.createdAt))');

        $qb->andWhere('s.'.$field.' IS NOT NULL');

        if ($removeImported) {
            $qb->andWhere('s.isImported IS NULL OR s.isImported = 0');
        }
        if ($territory) {
            $qb->andWhere('s.territory = :territory')->setParameter('territory', $territory);
        }

        return $qb->getQuery()
            ->getSingleScalarResult();
    }

    public function countFiltered(StatisticsFilters $statisticsFilters): ?int
    {
        $qb = $this->createQueryBuilder('s');

        $qb->select('COUNT(s.id)');
        $qb = self::addFiltersToQuery($qb, $statisticsFilters);

        return $qb->getQuery()
            ->getSingleScalarResult();
    }

    public function countByMonthFiltered(StatisticsFilters $statisticsFilters): array
    {
        $qb = $this->createQueryBuilder('s');
        $qb->select('COUNT(s.id) AS count, MONTH(s.createdAt) AS month, YEAR(s.createdAt) AS year');

        $qb = self::addFiltersToQuery($qb, $statisticsFilters);

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

        $qb->select('AVG(s.scoreCreation)');
        $qb->andWhere('s.scoreCreation IS NOT NULL');

        $qb = self::addFiltersToQuery($qb, $statisticsFilters);

        return $qb->getQuery()
            ->getSingleScalarResult();
    }

    public function countBySituationFiltered(StatisticsFilters $statisticsFilters): array
    {
        $qb = $this->createQueryBuilder('s');
        $qb->select('COUNT(s.id) AS count, sit.id, sit.menuLabel');
        $qb->leftJoin('s.situations', 'sit');

        $qb = self::addFiltersToQuery($qb, $statisticsFilters);

        $qb->andWhere('sit.isActive = :isActive')->setParameter('isActive', true);
        $qb->groupBy('sit.id');

        return $qb->getQuery()
            ->getResult();
    }

    public function countByCriticiteFiltered(StatisticsFilters $statisticsFilters): array
    {
        $qb = $this->createQueryBuilder('s');
        $qb->select('COUNT(s.id) AS count, crit.id, crit.label');
        $qb->leftJoin('s.criticites', 'crit');

        $qb = self::addFiltersToQuery($qb, $statisticsFilters);

        $qb->andWhere('crit.isArchive = :isArchive')->setParameter('isArchive', false);
        $qb->groupBy('crit.id');

        return $qb->getQuery()
            ->getResult();
    }

    public function countByStatusFiltered(StatisticsFilters $statisticsFilters): array
    {
        $qb = $this->createQueryBuilder('s');
        $qb->select('COUNT(s.id) as count')
            ->addSelect('s.statut');

        $qb = self::addFiltersToQuery($qb, $statisticsFilters);

        $qb->indexBy('s', 's.statut');
        $qb->groupBy('s.statut');

        return $qb->getQuery()
            ->getResult();
    }

    public function countByCriticitePercentFiltered(StatisticsFilters $statisticsFilters): array
    {
        $qb = $this->createQueryBuilder('s');
        $qb->select('COUNT(s.id) as count')
            ->addSelect('case
                when s.scoreCreation >= 0 and s.scoreCreation < 25 then \''.CriticitePercentStatisticProvider::CRITICITE_VERY_WEAK.'\'
                when s.scoreCreation >= 25 and s.scoreCreation < 51 then \''.CriticitePercentStatisticProvider::CRITICITE_WEAK.'\'
                when s.scoreCreation >= 51 and s.scoreCreation <= 75 then \''.CriticitePercentStatisticProvider::CRITICITE_STRONG.'\'
                else \''.CriticitePercentStatisticProvider::CRITICITE_VERY_STRONG.'\'
                end as range');

        $qb = self::addFiltersToQuery($qb, $statisticsFilters);

        $qb->groupBy('range');

        return $qb->getQuery()
            ->getResult();
    }

    public function countByVisiteFiltered(StatisticsFilters $statisticsFilters): array
    {
        $qb = $this->createQueryBuilder('s');
        $qb->select('COUNT(s.id) as count')
            ->addSelect('case
            when s.dateVisite IS NULL then \'Non\'
            else \'Oui\'
            end as visite');

        $qb = self::addFiltersToQuery($qb, $statisticsFilters);

        $qb->groupBy('visite');

        return $qb->getQuery()
            ->getResult();
    }

    public function countByMotifClotureFiltered(StatisticsFilters $statisticsFilters): array
    {
        $qb = $this->createQueryBuilder('s');
        $qb->select('COUNT(s.id) AS count, s.motifCloture')

            ->where('s.motifCloture IS NOT NULL')
            ->andWhere('s.motifCloture != \'0\'')
            ->andWhere('s.closedAt IS NOT NULL');

        $qb = self::addFiltersToQuery($qb, $statisticsFilters);

        $qb->groupBy('s.motifCloture');

        return $qb->getQuery()
            ->getResult();
    }

    public static function addFiltersToQuery(QueryBuilder $qb, StatisticsFilters $filters): QueryBuilder
    {
        // Is the status defined?
        if ('' != $filters->getStatut() && 'all' != $filters->getStatut()) {
            $statutParameter = [];
            switch ($filters->getStatut()) {
                case 'new':
                    $statutParameter[] = Signalement::STATUS_NEED_VALIDATION;
                    break;
                case 'active':
                    $statutParameter[] = Signalement::STATUS_NEED_PARTNER_RESPONSE;
                    $statutParameter[] = Signalement::STATUS_ACTIVE;
                    break;
                case 'closed':
                    $statutParameter[] = Signalement::STATUS_CLOSED;
                    break;
                default:
                    break;
            }
            // If we count the Refused status
            if ($filters->isCountRefused()) {
                $statutParameter[] = Signalement::STATUS_REFUSED;
            }
            // If we count the Archived status
            if ($filters->isCountArchived()) {
                $statutParameter[] = Signalement::STATUS_ARCHIVED;
            }

            $qb->andWhere('s.statut IN (:statutSelected)')
            ->setParameter('statutSelected', $statutParameter);

        // We're supposed to keep all statuses, but we remove at least the Archived
        } else {
            // If we don't want Refused status
            if (!$filters->isCountRefused()) {
                $qb->andWhere('s.statut != :statutRefused')
                ->setParameter('statutRefused', Signalement::STATUS_REFUSED);
            }
            // If we don't want Archived status
            if (!$filters->isCountArchived()) {
                $qb->andWhere('s.statut != :statutArchived')
                ->setParameter('statutArchived', Signalement::STATUS_ARCHIVED);
            }
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
            $qb->andWhere('s.villeOccupant IN (:communes)')
                ->setParameter('communes', $filters->getCommunes());
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
}
