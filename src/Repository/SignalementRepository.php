<?php

namespace App\Repository;

use App\Entity\Signalement;
use App\Entity\Territory;
use App\Entity\User;
use App\Service\SearchFilterService;
use DateTime;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\Query\Expr\Join;
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

    public function findAllWithGeoData($user, $options, int $offset, Territory|null $territory)
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

    public function findAllWithAffectations($year)
    {
        return $this->createQueryBuilder('s')
            ->where('s.statut != 7')
            ->andWhere('YEAR(s.createdAt) = '.$year)
            ->leftJoin('s.affectations', 'affectations')
            ->addSelect('affectations', 's')
            ->getQuery()
            ->getResult();
    }

    public function countAll(bool $removeImported = false)
    {
        $qb = $this->createQueryBuilder('s');
        $qb->select('COUNT(s.id)');
        $qb->andWhere('s.statut != :statutArchived')
            ->setParameter('statutArchived', Signalement::STATUS_ARCHIVED);
        $qb->andWhere('s.statut != :statutRefused')
            ->setParameter('statutRefused', Signalement::STATUS_REFUSED);

        if ($removeImported) {
            $qb->andWhere('s.isImported IS NULL OR s.isImported = 0');
        }

        return $qb->getQuery()
            ->getSingleScalarResult();
    }

    public function countImported()
    {
        $qb = $this->createQueryBuilder('s');
        $qb->select('COUNT(s.id)');
        $qb->andWhere('s.statut != :statutArchived')
            ->setParameter('statutArchived', Signalement::STATUS_ARCHIVED);
        $qb->andWhere('s.isImported = 1');

        return $qb->getQuery()
            ->getSingleScalarResult();
    }

    public function countByStatus(Territory|null $territory, int|null $year = null, bool $removeImported = false)
    {
        $qb = $this->createQueryBuilder('s');
        $qb->select('COUNT(s.id) as count')
            ->addSelect('s.statut');

        if ($removeImported) {
            $qb->andWhere('s.isImported IS NULL OR s.isImported = 0');
        }

        if ($territory) {
            $qb->andWhere('s.territory = :territory')->setParameter('territory', $territory);
        }
        if ($year) {
            $qb->andWhere('YEAR(s.createdAt) = :year')->setParameter('year', $year);
        }
        $qb->indexBy('s', 's.statut');
        $qb->groupBy('s.statut');

        return $qb->getQuery()
            ->getResult();
    }

    public function countValidated(bool $removeImported = false)
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

    public function countClosed(bool $removeImported = false)
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

    public function countByCity()
    {
        $qb = $this->createQueryBuilder('s');
        $qb->select('COUNT(s.id) as count')
            ->addSelect('s.villeOccupant');
        $qb->indexBy('s', 's.villeOccupant');
        $qb->groupBy('s.villeOccupant');

        return $qb->getQuery()
            ->getResult();
    }

    public function countByTerritory(bool $removeImported = false)
    {
        $qb = $this->createQueryBuilder('s');
        $qb->select('COUNT(s.id) AS count, t.zip, t.name, t.id');
        $qb->leftJoin('s.territory', 't');
        if ($removeImported) {
            $qb->andWhere('s.isImported IS NULL OR s.isImported = 0');
        }
        $qb->groupBy('t.id');

        return $qb->getQuery()
            ->getResult();
    }

    public function countByMonth(Territory|null $territory, int|null $year, bool $removeImported = false)
    {
        $qb = $this->createQueryBuilder('s');
        $qb->select('COUNT(s.id) AS count, MONTH(s.createdAt) AS month, YEAR(s.createdAt) AS year');

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

    public function countBySituation(Territory|null $territory, int|null $year, bool $removeImported = false)
    {
        $qb = $this->createQueryBuilder('s');
        $qb->select('COUNT(s.id) AS count, sit.id, sit.menuLabel');
        $qb->leftJoin('s.situations', 'sit');

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

    public function countByMotifCloture(Territory|null $territory, int|null $year, bool $removeImported = false)
    {
        $qb = $this->createQueryBuilder('s');
        $qb->select('COUNT(s.id) AS count, s.motifCloture');

        $qb->andWhere('s.motifCloture IS NOT NULL');
        $qb->andWhere('s.motifCloture != \'0\'');
        $qb->andWhere('s.closedAt IS NOT NULL');

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

    /**
     * @throws NonUniqueResultException
     */
    public function findByUuid($uuid)
    {
        $qb = $this->createQueryBuilder('s')
            ->where('s.uuid = :uuid')
            ->setParameter('uuid', $uuid);
        $qb
            ->leftJoin('s.situations', 'situations')
            ->leftJoin('s.tags', 'tags')
            ->leftJoin('s.affectations', 'affectations')
            ->leftJoin('situations.criteres', 'criteres')
            ->leftJoin('criteres.criticites', 'criticites')
            ->leftJoin('affectations.partner', 'partner')
            ->addSelect('situations', 'affectations', 'criteres', 'criticites', 'partner');

        return $qb->getQuery()->getOneOrNullResult();
    }

    public function findByStatusAndOrCityForUser(User|UserInterface $user = null, array $options, int|null $export)
    {
        $pageSize = $export ?? self::ARRAY_LIST_PAGE_SIZE;
        $firstResult = (($options['page'] ?? 1) - 1) * $pageSize;
        $qb = $this->createQueryBuilder('s');
        $qb->where('s.statut != :status')
            ->setParameter('status', Signalement::STATUS_ARCHIVED);
        if (!$export) {
            $qb->select('PARTIAL s.{id,uuid,reference,nomOccupant,prenomOccupant,adresseOccupant,cpOccupant,villeOccupant,scoreCreation,statut,createdAt,geoloc}');
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

    /**
     * Query called by statistics with filters.
     */
    public function findByFilters(string $statut, bool $countRefused, ?DateTime $dateStart, ?DateTime $dateEnd, string $type, ?int $territory, ?array $etiquettes, ?array $communes): array
    {
        $qb = $this->createQueryBuilder('s');

        // Is the status defined?
        if ('' != $statut && 'all' != $statut) {
            $statutParameter = [];
            switch ($statut) {
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
            if ($countRefused) {
                $statutParameter[] = Signalement::STATUS_REFUSED;
            }

            $qb->andWhere('s.statut IN (:statutSelected)')
            ->setParameter('statutSelected', $statutParameter);

        // We're supposed to keep all statuses, but we remove at least the Archived
        } else {
            $qb->andWhere('s.statut != :statutArchived')
            ->setParameter('statutArchived', Signalement::STATUS_ARCHIVED);
            // If we don't want refused status
            if (!$countRefused) {
                $qb->andWhere('s.statut != :statutRefused')
                ->setParameter('statutRefused', Signalement::STATUS_REFUSED);
            }
        }

        // Filter on creation date
        if (null !== $dateStart) {
            $qb->andWhere('s.createdAt >= :dateStart')
            ->setParameter('dateStart', $dateStart)
            ->andWhere('s.createdAt <= :dateEnd')
            ->setParameter('dateEnd', $dateEnd);
        }

        // Filter on Signalement type (logement social)
        if ('' != $type && 'all' != $type) {
            switch ($type) {
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

        if ($territory) {
            $qb->andWhere('s.territory = :territoryId')
                ->setParameter('territoryId', $territory);
        }

        if ($etiquettes) {
            $qb->leftJoin('s.tags', 'tags');
            $qb->andWhere('tags IN (:tags)')
                ->setParameter('tags', $etiquettes);
        }

        if ($communes) {
            $qb->andWhere('s.villeOccupant IN (:communes)')
                ->setParameter('communes', $communes);
        }

        return $qb->getQuery()
                ->getResult();
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

    public function findDuplicatedReferences(Territory $territory)
    {
        $year = (new \DateTime())->format('Y');
        $queryBuilder = $this->createQueryBuilder('s')
            ->select('s.reference')
            ->addSelect("SUBSTRING_INDEX(s.reference, '-', 1) AS year")
            ->addSelect("CAST(SUBSTRING_INDEX(s.reference, '-', -1) AS SIGNED) AS reference_index")
            ->addSelect('COUNT(s.id) AS nb_signalement')
            ->where('s.territory = :territory')
            ->setParameter('territory', $territory)
            ->groupBy('s.reference')
            ->having('nb_signalement > 1')
            ->orderBy('reference_index', 'ASC');

        return $queryBuilder->getQuery()->getArrayResult();
    }

    public function findUsersEmailAffectedToSignalement(int $signalementId)
    {
        $queryBuilder = $this->createQueryBuilder('s');
        $queryBuilder
            ->select('u.email')
            ->innerJoin('s.affectations', 'a')
            ->innerJoin('a.partner', 'p')
            ->innerJoin('p.users', 'u')
            ->where('s.id = :signalement_id')
            ->setParameter('signalement_id', $signalementId)
            ->andWhere('u.statut = '.User::STATUS_ACTIVE);

        $usersEmail = array_map(function ($value) {
            return $value['email'];
        }, $queryBuilder->getQuery()->getArrayResult());

        return $usersEmail;
    }
}
