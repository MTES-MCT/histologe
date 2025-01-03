<?php

namespace App\Repository;

use App\Dto\CountSignalement;
use App\Dto\StatisticsFilters;
use App\Entity\Affectation;
use App\Entity\Enum\MotifCloture;
use App\Entity\Enum\PartnerType;
use App\Entity\Enum\Qualification;
use App\Entity\Partner;
use App\Entity\Signalement;
use App\Entity\Territory;
use App\Entity\User;
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
    private const DELAY_VISITE_AFTER_AFFECTATION = 15;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Affectation::class);
    }

    public function save(Affectation $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Affectation $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function countByStatusForUser($user, array $territories, ?Qualification $qualification = null, ?array $qualificationStatuses = null)
    {
        $qb = $this->createQueryBuilder('a')
            ->select('COUNT(a.signalement) as count')
            ->leftJoin('a.signalement', 's', 'WITH', 's = a.signalement')
            ->andWhere('s.statut != 7')
            ->addSelect('a.statut')
            ->andWhere('a.partner IN (:partners)')
            ->setParameter('partners', $user->getPartners());
        if (\count($territories)) {
            $qb->leftJoin('a.partner', 'p')
                ->andWhere('p.territory IN (:territories)')
                ->setParameter('territories', $territories);
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
        if (!$statisticsFilters->getPartners() || $statisticsFilters->getPartners()->isEmpty()) {
            $qb->leftJoin('a.partner', 'partner');
        }

        $qb = SignalementRepository::addFiltersToQuery($qb, $statisticsFilters);

        return $qb->getQuery()
            ->getResult();
    }

    /**
     * @return Affectation[]
     */
    public function findAffectationSubscribedToEsabora(
        PartnerType $partnerType,
        ?bool $isSynchronized = true,
        ?string $uuidSignalement = null,
        ?Territory $territory = null,
    ): array {
        $qb = $this->createQueryBuilder('a');
        $qb = $qb
            ->innerJoin('a.partner', 'p')
            ->innerJoin('a.signalement', 's')
            ->where('p.esaboraUrl IS NOT NULL AND p.esaboraToken IS NOT NULL AND p.isEsaboraActive = 1')
            ->andWhere('s.statut != :signalement_statut')
            ->setParameter('signalement_statut', Signalement::STATUS_ARCHIVED)
            ->andWhere('p.type = :partner_type')
            ->setParameter('partner_type', $partnerType);

        if (null !== $isSynchronized) {
            $qb->andWhere('a.isSynchronized = :is_synchronized')
                ->setParameter('is_synchronized', $isSynchronized);
        }

        if (null !== $uuidSignalement) {
            $qb->andWhere('s.uuid LIKE :uuid_signalement')
                ->setParameter('uuid_signalement', $uuidSignalement);
        }

        if (null !== $territory) {
            $qb->andWhere('a.territory = :territory')->setParameter('territory', $territory);
        }

        return $qb->getQuery()->getResult();
    }

    public function countAffectationPartner(array $territories): array
    {
        $qb = $this->createQueryBuilder('a');
        $qb->select('SUM(CASE WHEN a.statut = :statut_wait THEN 1 ELSE 0 END) AS waiting')
            ->addSelect('SUM(CASE WHEN a.statut = :statut_refused THEN 1 ELSE 0 END) AS refused')
            ->addSelect('t.zip', 'p.nom')
            ->innerJoin('a.territory', 't')
            ->innerJoin('a.partner', 'p')
            ->setParameter('statut_wait', Affectation::STATUS_WAIT)
            ->setParameter('statut_refused', Affectation::STATUS_REFUSED);

        if (\count($territories)) {
            $qb->andWhere('a.territory IN (:territories)')->setParameter('territories', $territories);
        }

        $qb->groupBy('t.zip', 'p.nom');

        return $qb->getQuery()->getResult();
    }

    public function countAffectationForUser(User $user, array $territories): int
    {
        $qb = $this->createQueryBuilder('a');
        $qb->select('COUNT(a.id) as nb_affectation')
            ->where('a.partner IN (:partners)')
            ->setParameter('partners', $user->getPartners())
            ->andWhere('a.statut = :statut_wait')
            ->setParameter('statut_wait', Affectation::STATUS_WAIT);
        if (\count($territories)) {
            $qb->andWhere('a.territory IN (:territories)')->setParameter('territories', $territories);
        }

        return $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * @throws NonUniqueResultException
     */
    public function countSignalementForUser(User $user, array $territories): CountSignalement
    {
        $qb = $this->createQueryBuilder('a');
        $qb->select(
            \sprintf(
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
            ->andWhere('a.partner IN (:partners)')
            ->setParameter('statut_archived', Signalement::STATUS_ARCHIVED)
            ->setParameter('partners', $user->getPartners());

        if (\count($territories)) {
            $qb->andWhere('s.territory IN (:territories)')->setParameter('territories', $territories);
        }

        return $qb->getQuery()->getOneOrNullResult();
    }

    public function findAffectationWithQualification(Qualification $qualification, Signalement $signalement)
    {
        $qb = $this->createQueryBuilder('a');
        $qb->select('p.id, p.nom')
            ->where('a.signalement = :signalement')
            ->setParameter('signalement', $signalement)
            ->innerJoin('a.partner', 'p')
            ->andWhere('REGEXP(p.competence, :regexp) = true')
            ->setParameter('regexp', '(^'.$qualification->name.',)|(,'.$qualification->name.',)|(,'.$qualification->name.'$)|(^'.$qualification->name.'$)');

        return $qb->getQuery()->getResult();
    }

    public function findAcceptedAffectationsFromVisitesPartner(): array
    {
        $qb = $this->createQueryBuilder('a');
        $qb
            ->innerJoin('a.partner', 'p')
            ->innerJoin('a.signalement', 's')
            ->where('a.statut = :statusAffectation')
            ->setParameter('statusAffectation', Affectation::STATUS_ACCEPTED)
            ->andWhere('s.statut = :statusSignalement')
            ->setParameter('statusSignalement', Signalement::STATUS_ACTIVE)
            ->andWhere('p.competence LIKE :qualification')
            ->setParameter('qualification', Qualification::VISITES->name)
            ->andWhere('DATEDIFF(CURRENT_DATE(),a.answeredAt) = :day_delay')
            ->setParameter('day_delay', self::DELAY_VISITE_AFTER_AFFECTATION);

        return $qb->getQuery()->getResult();
    }

    public function deleteByStatusAndSignalement(int $status, Signalement $signalement): void
    {
        $qb = $this->createQueryBuilder('a');
        $qb->delete()
            ->where('a.statut = :status')
            ->andWhere('a.signalement = :signalement')
            ->setParameter('status', $status)
            ->setParameter('signalement', $signalement)
            ->getQuery()
            ->execute();
    }

    public function updateStatusBySignalement(int $status, Signalement $signalement): void
    {
        $qb = $this->createQueryBuilder('a');
        $qb->update()
            ->set('a.statut', ':status')
            ->where('a.signalement = :signalement')
            ->setParameter('status', $status)
            ->setParameter('signalement', $signalement)
            ->getQuery()
            ->execute();
    }

    public function closeBySignalement(Signalement $signalement, MotifCloture $motif, User $user): void
    {
        $qb = $this->createQueryBuilder('a');
        $qb->update()
            ->set('a.statut', ':status')
            ->set('a.answeredBy', ':answeredBy')
            ->set('a.motifCloture', ':motif')
            ->where('a.signalement = :signalement')
            ->setParameter('status', Affectation::STATUS_CLOSED)
            ->setParameter('answeredBy', $user)
            ->setParameter('motif', $motif)
            ->setParameter('signalement', $signalement)
            ->getQuery()
            ->execute();
    }

    public function deleteAffectationsByPartner(Partner $partner): void
    {
        $qb = $this->createQueryBuilder('a')
            ->delete()
            ->andWhere('a.statut IN (:statuses)')
            ->andWhere('a.partner = :partner')
            ->setParameter('statuses', [Affectation::STATUS_ACCEPTED, Affectation::STATUS_WAIT])
            ->setParameter('partner', $partner);

        $qb->getQuery()->execute();
    }
}
