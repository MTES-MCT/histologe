<?php

namespace App\Repository;

use App\Entity\Enum\PartnerType;
use App\Entity\Enum\Qualification;
use App\Entity\Enum\UserStatus;
use App\Entity\Partner;
use App\Entity\Territory;
use App\Entity\User;
use App\Entity\UserPartner;
use App\Service\ListFilters\SearchArchivedPartner;
use App\Service\ListFilters\SearchPartner;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Partner>
 *
 * @method Partner|null find($id, $lockMode = null, $lockVersion = null)
 * @method Partner|null findOneBy(array<string, mixed> $criteria, array<string, mixed>|null $orderBy = null)
 * @method Partner[]    findAll()
 * @method Partner[]    findBy(array<string, mixed> $criteria, array<string, mixed>|null $orderBy = null, $limit = null, $offset = null)
 */
class PartnerRepository extends ServiceEntityRepository
{
    public function __construct(
        private readonly TerritoryRepository $territoryRepository,
        private readonly AffectationRepository $affectationRepository,
        ManagerRegistry $registry,
    ) {
        parent::__construct($registry, Partner::class);
    }

    public function getPartnersQueryBuilder(?Territory $territory): QueryBuilder
    {
        $queryBuilder = $this->createQueryBuilder('p')->where('p.isArchive != 1');

        if ($territory) {
            $queryBuilder->andWhere('p.territory = :territory')->setParameter('territory', $territory);
        }

        return $queryBuilder;
    }

    /**
     * @return Paginator<Partner>
     */
    public function findFilteredPaginated(SearchPartner $searchPartner, int $maxResult): Paginator
    {
        return $this->getPartners(
            $maxResult,
            $searchPartner,
        );
    }

    /**
     * @return Paginator<Partner>
     */
    public function getPartners(
        int $maxResult,
        SearchPartner $searchPartner,
    ): Paginator {
        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = $this->getPartnersQueryBuilder($searchPartner->getTerritoire());
        $queryBuilder->select('p', 'z', 'ez', 'up', 'u', 'epcis')
            ->leftJoin('p.zones', 'z')
            ->leftJoin('p.excludedZones', 'ez')
            ->leftJoin('p.userPartners', 'up')
            ->leftJoin('up.user', 'u')
            ->leftJoin('p.epcis', 'epcis');

        $queryBuilder->addSelect(
            '(CASE
                WHEN (p.email IS NOT NULL AND p.email != \'\' AND p.emailNotifiable = 1) THEN 1
                WHEN EXISTS (
                    SELECT 1
                    FROM '.UserPartner::class.' up2
                    JOIN up2.user u2
                    WHERE up2.partner = p
                    AND u2.email IS NOT NULL
                    AND u2.statut LIKE \''.UserStatus::ACTIVE->value.'\'
                    AND u2.isMailingActive = 1
                ) THEN 1
                ELSE 0
            END) AS isNotifiable'
        );
        $user = $searchPartner->getUser();
        if (!$user->isSuperAdmin() && !$searchPartner->getTerritoire()) {
            $queryBuilder->andWhere('p.territory IN (:territories)')
                ->setParameter('territories', $user->getPartnersTerritories());
        }

        if ($searchPartner->getIsNotNotifiable()) {
            $queryBuilder->andHaving('isNotifiable = 0');
        }

        if ($searchPartner->getIsOnlyInterconnected()) {
            $queryBuilder->andWhere('p.isEsaboraActive = 1 or p.isIdossActive = 1');
        } elseif (false === $searchPartner->getIsOnlyInterconnected()) {
            $queryBuilder->andWhere('p.isEsaboraActive = 0  and p.isIdossActive = 0');
        }

        if (!empty($searchPartner->getPartnerType())) {
            $queryBuilder
                ->andWhere('p.type = :type')
                ->setParameter('type', $searchPartner->getPartnerType());
        }

        if (!empty($searchPartner->getQueryPartner())) {
            $queryBuilder
                ->andWhere('LOWER(p.nom) LIKE :usersterms
                OR LOWER(p.email) LIKE :usersterms');
            $queryBuilder
                ->setParameter('usersterms', '%'.strtolower($searchPartner->getQueryPartner()).'%');
        }

        if (!empty($searchPartner->getOrderType())) {
            [$orderField, $orderDirection] = explode('-', $searchPartner->getOrderType());
            $queryBuilder->orderBy($orderField, $orderDirection);
        } else {
            $queryBuilder->orderBy('p.nom', 'ASC');
        }

        $firstResult = ($searchPartner->getPage() - 1) * $maxResult;
        $queryBuilder->setFirstResult($firstResult)->setMaxResults($maxResult);

        $paginator = new Paginator($queryBuilder->getQuery());

        return $paginator;
    }

    /**
     * @return array<string, Partner>
     */
    public function findAllList(?Territory $territory = null, ?User $user = null): array
    {
        $qb = $this->createQueryBuilder('p')
            ->where('p.isArchive != 1')
            ->orderBy('p.nom', 'ASC');
        if ($user && !$user->isSuperAdmin()) {
            $qb->andWhere('p.territory IN (:territories)')->setParameter('territories', $user->getPartnersTerritories());
        }
        if ($territory) {
            $qb->andWhere('p.territory = :territory')->setParameter('territory', $territory);
        }

        return $qb->indexBy('p', 'p.id')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return array<string, Partner>
     */
    public function findAllWithoutTerritory(): array
    {
        $qb = $this->createQueryBuilder('p')
            ->where('p.isArchive != 1')
            ->andWhere('p.territory IS NULL');

        return $qb->indexBy('p', 'p.id')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Paginator<Partner>
     */
    public function findFilteredArchivedPaginated(SearchArchivedPartner $searchArchivedPartner, int $maxResult): Paginator
    {
        $queryBuilder = $this->createQueryBuilder('p');

        $territory = $searchArchivedPartner->getTerritory() ? $this->territoryRepository->find($searchArchivedPartner->getTerritory()) : null;
        $builtOrCondition = '';
        if (empty($territory)) {
            $builtOrCondition .= ' OR p.territory IS NULL';
        }

        $queryBuilder
            ->where('p.isArchive = 1'.$builtOrCondition);

        if (!empty($territory)) {
            $queryBuilder
                ->andWhere('p.territory = :territory')
                ->setParameter('territory', $territory);
        }

        $filterTerms = $searchArchivedPartner->getQueryArchivedPartner();
        if (!empty($filterTerms)) {
            $queryBuilder
                ->andWhere('LOWER(p.nom) LIKE :usersterms OR LOWER(p.email) LIKE :usersterms')
                ->setParameter('usersterms', '%'.strtolower($filterTerms).'%');
        }

        if (!empty($searchArchivedPartner->getOrderType())) {
            [$orderField, $orderDirection] = explode('-', $searchArchivedPartner->getOrderType());
            $queryBuilder->orderBy($orderField, $orderDirection);
        } else {
            $queryBuilder->orderBy('p.nom', 'ASC');
        }

        $firstResult = ($searchArchivedPartner->getPage() - 1) * $maxResult;
        $queryBuilder->setFirstResult($firstResult)->setMaxResults($maxResult);

        return new Paginator($queryBuilder->getQuery(), false);
    }

    /**
     * @return array<string, Partner>
     */
    public function findPartnersWithQualification(Qualification $qualification, ?Territory $territory)
    {
        $qb = $this->createQueryBuilder('p');
        $qb->andWhere('REGEXP(p.competence, :regexp) = true')
            ->setParameter('regexp', '(^'.$qualification->name.',)|(,'.$qualification->name.',)|(,'.$qualification->name.'$)|(^'.$qualification->name.'$)');
        if ($territory) {
            $qb->andWhere('p.territory = :territory')
                ->setParameter('territory', $territory);
        }

        return $qb->indexBy('p', 'p.id')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return array<string, Partner>
     */
    public function findWithInsee(string $insee, ?Territory $territory = null): array
    {
        $qb = $this->createQueryBuilder('p');
        $qb->andWhere('p.insee LIKE :insee')
            ->setParameter('insee', '%'.$insee.'%');
        if ($territory) {
            $qb->andWhere('p.territory = :territory')
                ->setParameter('territory', $territory);
        }

        return $qb->indexBy('p', 'p.id')
            ->getQuery()
            ->getResult();
    }

    /**
     * @param list<int|string>  $partnerIds
     * @param list<PartnerType> $types
     *
     * @return array<int, Partner>
     */
    public function findByIds(array $partnerIds, array $types = []): array
    {
        if ([] === $partnerIds) {
            return [];
        }

        $qb = $this->createQueryBuilder('p', 'p.id')
            ->andWhere('p.id IN (:ids)')
            ->setParameter('ids', $partnerIds)
            ->orderBy('p.nom', 'ASC');

        if ([] !== $types) {
            $qb->andWhere('p.type IN (:types)')
                ->setParameter('types', $types);
        }

        return $qb->getQuery()->getResult();
    }
}
