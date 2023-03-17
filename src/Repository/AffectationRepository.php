<?php

namespace App\Repository;

use App\Dto\CountSignalement;
use App\Dto\StatisticsFilters;
use App\Entity\Affectation;
use App\Entity\Enum\Qualification;
use App\Entity\Partner;
use App\Entity\Signalement;
use App\Entity\Territory;
use App\Entity\User;
use App\Service\SearchFilterService;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @method Affectation|null find($id, $lockMode = null, $lockVersion = null)
 * @method Affectation|null findOneBy(array $criteria, array $orderBy = null)
 * @method Affectation[]    findAll()
 * @method Affectation[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AffectationRepository extends ServiceEntityRepository
{
    public const ARRAY_LIST_PAGE_SIZE = 30;

    public function __construct(ManagerRegistry $registry, private SearchFilterService $searchFilterService)
    {
        parent::__construct($registry, Affectation::class);
    }

    public function countByStatusForUser($user, Territory|null $territory, Qualification $qualification = null, array $qualificationStatuses = null)
    {
        $qb = $this->createQueryBuilder('a')
            ->select('COUNT(a.signalement) as count')
            ->andWhere('a.partner = :partner')
            ->leftJoin('a.signalement', 's', 'WITH', 's = a.signalement')
            ->andWhere('s.statut != 7')
            ->setParameter('partner', $user->getPartner())
            ->addSelect('a.statut');
        if ($territory) {
            $qb->andWhere('s.territory = :territory')
                ->setParameter('territory', $territory);
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

        return $qb->indexBy('a', 'a.statut')
            ->groupBy('a.statut')
            ->getQuery()
            ->getResult();
    }

    public function findByStatusAndOrCityForUser(User|UserInterface|null $user, array $options, int|null $export): Paginator|array
    {
        $page = (int) $options['page'];
        $pageSize = $export ?? self::ARRAY_LIST_PAGE_SIZE;
        $firstResult = (($page ?: 1) - 1) * $pageSize;
        $qb = $this->createQueryBuilder('a');
        $qb->where('s.statut != :status')
            ->setParameter('status', Signalement::STATUS_ARCHIVED);
        if (!$export) {
            $qb->select('a,PARTIAL s.{id,uuid,reference,nomOccupant,prenomOccupant,adresseOccupant,cpOccupant,villeOccupant,scoreCreation,statut,createdAt,geoloc,territory}');
        }
        $qb->leftJoin('a.signalement', 's')
            ->leftJoin('s.tags', 'tags')
            ->leftJoin('s.affectations', 'affectations')
            ->leftJoin('a.partner', 'partner')
            ->leftJoin('s.suivis', 'suivis')
            ->leftJoin('s.criteres', 'criteres')
            ->addSelect('s', 'partner', 'suivis', 'affectations');
        $stat = $statOr = null;
        if ($options['statuses']) {
            foreach ($options['statuses'] as $k => $statu) {
                if ($statu === (string) Signalement::STATUS_CLOSED) {
                    $options['statuses'][$k] = Affectation::STATUS_CLOSED;
                    $options['statuses'][\count($options['statuses'])] = Affectation::STATUS_REFUSED;
                } elseif ($statu === (string) Signalement::STATUS_ACTIVE) {
                    $options['statuses'][$k] = Affectation::STATUS_ACCEPTED;
                } elseif ($statu === (string) Signalement::STATUS_NEED_VALIDATION) {
                    $options['statuses'][$k] = Affectation::STATUS_WAIT;
                }
            }
            $qb->andWhere('a.statut IN (:statuses)');
            /* if ($statOr)
                 $qb->orWhere('a.statut IN (:statuses)');*/
            $qb->setParameter('statuses', $options['statuses']);
            unset($options['statuses']);
        }
        $qb = $this->searchFilterService->applyFilters($qb, $options);
        if ($user && $user->getTerritory()) {
            $qb->andWhere('s.territory = :territory')->setParameter('territory', $user->getTerritory());
        }
        if ($user && !$user->isSuperAdmin() && !$user->isTerritoryAdmin()) {
            $qb->andWhere(':partner IN (partner)')
                ->setParameter('partner', $user->getPartner());
        }

        $qb->orderBy('s.createdAt', 'DESC');
        if (!$export) {
            $qb->setFirstResult($firstResult)
                ->setMaxResults($pageSize)
                ->getQuery();

            return new Paginator($qb, true);
        }

        return $qb->getQuery()->getResult();
    }

    public function countByPartenaireFiltered(StatisticsFilters $statisticsFilters): array
    {
        $qb = $this->createQueryBuilder('a');
        $qb->select('a.id, a.statut, partner.id, partner.nom')
            ->leftJoin('a.signalement', 's')
            ->leftJoin('a.partner', 'partner');

        $qb = SignalementRepository::addFiltersToQuery($qb, $statisticsFilters);

        return $qb->getQuery()
            ->getResult();
    }

    /**
     * @return Affectation[]
     */
    public function findAffectationSubscribedToEsabora(): array
    {
        $qb = $this->createQueryBuilder('a');
        $qb = $qb->innerJoin('a.partner', 'p')
            ->where('p.esaboraUrl IS NOT NULL AND p.esaboraToken IS NOT NULL');

        return $qb->getQuery()->getResult();
    }

    public function countAffectationPartner(?Territory $territory = null): array
    {
        $qb = $this->createQueryBuilder('a');
        $qb->select('SUM(CASE WHEN a.statut = :statut_wait THEN 1 ELSE 0 END) AS waiting')
            ->addSelect('SUM(CASE WHEN a.statut = :statut_refused THEN 1 ELSE 0 END) AS refused')
            ->addSelect('t.zip', 'p.nom')
            ->innerJoin('a.territory', 't')
            ->innerJoin('a.partner', 'p')
            ->setParameter('statut_wait', Affectation::STATUS_WAIT)
            ->setParameter('statut_refused', Affectation::STATUS_REFUSED);

        if ($territory instanceof Territory) {
            $qb->andWhere('a.territory = :territory')->setParameter('territory', $territory);
        }

        $qb->groupBy('t.zip', 'p.nom');

        return $qb->getQuery()->getResult();
    }

    public function countAffectationByPartner(Partner $partner): int
    {
        $qb = $this->createQueryBuilder('a');
        $qb->select('COUNT(a.id) as nb_affectation')
            ->where('a.partner = :partner')
            ->setParameter('partner', $partner)
            ->andWhere('a.statut = :statut_wait')
            ->setParameter('statut_wait', Affectation::STATUS_WAIT);

        return $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * @throws NonUniqueResultException
     */
    public function countSignalementByPartner(Partner $partner): CountSignalement
    {
        $qb = $this->createQueryBuilder('a');
        $qb->select(sprintf('NEW %s(COUNT(a.id),
                    SUM(CASE WHEN a.statut = :statut_wait THEN 1 ELSE 0 END),
                    SUM(CASE WHEN a.statut = :statut_accepted THEN 1 ELSE 0 END),
                    SUM(CASE WHEN a.statut = :statut_closed THEN 1 ELSE 0 END),
                    SUM(CASE WHEN a.statut = :statut_refused THEN 1 ELSE 0 END))',
            CountSignalement::class)
        )
            ->setParameter('statut_wait', Affectation::STATUS_WAIT)
            ->setParameter('statut_accepted', Affectation::STATUS_ACCEPTED)
            ->setParameter('statut_closed', Affectation::STATUS_CLOSED)
            ->setParameter('statut_refused', Affectation::STATUS_REFUSED)
            ->innerJoin('a.partner', 'p')
            ->innerJoin('a.signalement', 's')
            ->where('s.statut != :statut_archived')
            ->andWhere('a.partner = :partner')
            ->setParameter('statut_archived', Signalement::STATUS_ARCHIVED)
            ->setParameter('partner', $partner);

        return $qb->getQuery()->getOneOrNullResult();
    }
}
