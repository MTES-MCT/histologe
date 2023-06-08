<?php

namespace App\Repository;

use App\Dto\CountSignalement;
use App\Dto\StatisticsFilters;
use App\Entity\Affectation;
use App\Entity\Enum\PartnerType;
use App\Entity\Enum\Qualification;
use App\Entity\Partner;
use App\Entity\Signalement;
use App\Entity\Territory;
use App\Service\SearchFilterService;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Affectation|null find($id, $lockMode = null, $lockVersion = null)
 * @method Affectation|null findOneBy(array $criteria, array $orderBy = null)
 * @method Affectation[]    findAll()
 * @method Affectation[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AffectationRepository extends ServiceEntityRepository
{
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

    public function countByPartenaireFiltered(StatisticsFilters $statisticsFilters): array
    {
        $qb = $this->createQueryBuilder('a');
        $qb->select('a.id, a.statut, partner.id, partner.nom')
            ->leftJoin('a.signalement', 's');
        if (null === $statisticsFilters->getPartner()) {
            $qb->leftJoin('a.partner', 'partner');
        }

        $qb = SignalementRepository::addFiltersToQuery($qb, $statisticsFilters);

        return $qb->getQuery()
            ->getResult();
    }

    /**
     * @return Affectation[]
     */
    public function findAffectationSubscribedToEsabora(PartnerType $partnerType, ?string $uuidSignalement = null): array
    {
        $qb = $this->createQueryBuilder('a');
        $qb = $qb->innerJoin('a.partner', 'p')
            ->where('p.esaboraUrl IS NOT NULL AND p.esaboraToken IS NOT NULL AND p.isEsaboraActive = 1')
            ->andWhere('p.type = :partner_type')
            ->setParameter('partner_type', $partnerType);

        if (null !== $uuidSignalement) {
            $qb->innerJoin('a.signalement', 's')
                ->andWhere('s.uuid LIKE :uuid_signalement')
                ->setParameter('uuid_signalement', $uuidSignalement);
        }

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
        $qb->select(
            sprintf(
                'NEW %s(COUNT(a.id),
                    SUM(CASE WHEN a.statut = :statut_wait THEN 1 ELSE 0 END),
                    SUM(CASE WHEN a.statut = :statut_accepted THEN 1 ELSE 0 END),
                    SUM(CASE WHEN a.statut = :statut_closed THEN 1 ELSE 0 END),
                    SUM(CASE WHEN a.statut = :statut_refused THEN 1 ELSE 0 END))',
                CountSignalement::class
            )
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
