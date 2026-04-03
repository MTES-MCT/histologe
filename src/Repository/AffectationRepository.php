<?php

namespace App\Repository;

use App\Dto\StatisticsFilters;
use App\Entity\Affectation;
use App\Entity\Enum\AffectationStatus;
use App\Entity\Enum\PartnerType;
use App\Entity\Enum\Qualification;
use App\Entity\Enum\SignalementStatus;
use App\Entity\JobEvent;
use App\Entity\Signalement;
use App\Entity\Territory;
use App\Service\ListFilters\SearchAffectationWithoutSubscription;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Affectation>
 *
 * @method Affectation|null find($id, $lockMode = null, $lockVersion = null)
 * @method Affectation|null findOneBy(array $criteria, array $orderBy = null)
 * @method Affectation[]    findAll()
 * @method Affectation[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AffectationRepository extends ServiceEntityRepository
{
    private const int DELAY_VISITE_AFTER_AFFECTATION = 15;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Affectation::class);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function countByPartenaireFiltered(StatisticsFilters $statisticsFilters): array
    {
        $qb = $this->createQueryBuilder('a');
        $qb->select('a.id, a.statut, partner.id, partner.nom')
            ->leftJoin('a.signalement', 's');
        if (!$statisticsFilters->getPartners() || $statisticsFilters->getPartners()->isEmpty()) {
            $qb->leftJoin('a.partner', 'partner');
        }

        $qb = SignalementRepository::addFiltersToQueryBuilder($qb, $statisticsFilters);

        return $qb->getQuery()
            ->getResult();
    }

    /**
     * @param PartnerType|PartnerType[] $partnerType
     *
     * @return array<int, array<string, mixed>>
     */
    public function findAffectationSubscribedToEsabora(
        PartnerType|array $partnerType,
        ?bool $isSynchronized = true,
        ?string $uuidSignalement = null,
        ?Territory $territory = null,
    ): array {
        $qb = $this->createQueryBuilder('a');
        $qb->select('a AS affectation', 's.uuid AS signalement_uuid');
        $qb = $qb
            ->innerJoin('a.partner', 'p')
            ->innerJoin('a.signalement', 's')
            ->where('p.esaboraUrl IS NOT NULL AND p.esaboraToken IS NOT NULL AND p.isEsaboraActive = 1')
            ->andWhere('s.statut NOT IN (:signalement_status_list)')
            ->setParameter('signalement_status_list', SignalementStatus::excludedStatuses());

        if (is_array($partnerType)) {
            $qb->andWhere('p.type IN (:partner_types)')->setParameter('partner_types', $partnerType);
        } else {
            $qb->andWhere('p.type = :partner_type')->setParameter('partner_type', $partnerType);
        }

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

    /**
     * @return array<int, Affectation>
     */
    public function findAffectationWithQualification(Qualification $qualification, Signalement $signalement): array
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

    /**
     * @return array<int, Affectation>
     */
    public function findAcceptedAffectationsFromVisitesPartner(): array
    {
        $qb = $this->createQueryBuilder('a');
        $qb
            ->innerJoin('a.partner', 'p')
            ->innerJoin('a.signalement', 's')
            ->where('a.statut = :statusAffectation')
            ->setParameter('statusAffectation', AffectationStatus::ACCEPTED->value)
            ->andWhere('s.statut = :statusSignalement')
            ->setParameter('statusSignalement', SignalementStatus::ACTIVE->value)
            ->andWhere('p.competence LIKE :qualification')
            ->setParameter('qualification', Qualification::VISITES->name)
            ->andWhere('DATEDIFF(CURRENT_DATE(),a.answeredAt) = :day_delay')
            ->setParameter('day_delay', self::DELAY_VISITE_AFTER_AFFECTATION);

        return $qb->getQuery()->getResult();
    }

    public function updateStatusBySignalement(AffectationStatus $status, Signalement $signalement): void
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

    /**
     * @return array<int, Affectation>
     */
    public function findAffectationSubscribedToIdoss(
        string $uuidSignalement,
    ): array {
        $qb = $this->createQueryBuilder('a');
        $qb->select('a')
            ->innerJoin('a.partner', 'p')
            ->innerJoin('a.signalement', 's')
            ->where('p.isIdossActive = 1')
            ->andWhere('p.idossUrl IS NOT NULL')
            ->andWhere('s.statut NOT IN (:signalement_status_list)')
            ->setParameter('signalement_status_list', SignalementStatus::excludedStatuses())
            ->andWhere('s.uuid LIKE :uuid_signalement')
            ->setParameter('uuid_signalement', $uuidSignalement);

        return $qb->getQuery()->getResult();
    }

    /**
     * @return Paginator<Affectation>
     */
    public function findWithoutSubscriptionFilteredPaginated(SearchAffectationWithoutSubscription $searchAffectation, int $maxResult): Paginator
    {
        // Sous-requête pour identifier les affectations avec abonnements
        $subQb = $this->getEntityManager()->createQueryBuilder();
        $subQb->select('DISTINCT a2.id')
            ->from(Affectation::class, 'a2')
            ->innerJoin('a2.partner', 'p2')
            ->innerJoin('p2.userPartners', 'up2')
            ->innerJoin('up2.user', 'u2')
            ->innerJoin('u2.userSignalementSubscriptions', 'uss2')
            ->where('a2.statut = :status')
            ->andWhere('uss2.signalement = a2.signalement');

        $qb = $this->createQueryBuilder('a');
        $qb->select('a', 'p', 's', 't', 'su')
            ->innerJoin('a.partner', 'p')
            ->innerJoin('a.signalement', 's')
            ->innerJoin('s.territory', 't')
            ->leftJoin('s.signalementUsager', 'su')
            ->where('a.statut = :status')->setParameter('status', AffectationStatus::ACCEPTED)
            ->andWhere($qb->expr()->not($qb->expr()->in('a.id', $subQb->getDQL())));

        if (!empty($searchAffectation->getOrderType())) {
            [$orderField, $orderDirection] = explode('-', $searchAffectation->getOrderType());
            $qb->orderBy($orderField, $orderDirection);
        } else {
            $qb->orderBy('a.createdAt', 'DESC');
        }

        if (null !== $searchAffectation->getTerritory()) {
            $qb->andWhere('s.territory = :territory')->setParameter('territory', $searchAffectation->getTerritory());
        }

        if (null !== $searchAffectation->getSignalementStatus()) {
            $qb->andWhere('s.statut = :signalementStatus')->setParameter('signalementStatus', $searchAffectation->getSignalementStatus());
        }

        $firstResult = ($searchAffectation->getPage() - 1) * $maxResult;
        $qb->setFirstResult($firstResult)->setMaxResults($maxResult);

        return new Paginator($qb->getQuery());
    }

    /**
     * @param array<int, PartnerType> $partnerTypes
     *
     * @return array<int, Affectation>
     */
    public function findAffectationsWithFailedJobEvents(
        string $service,
        string $action,
        array $partnerTypes = [],
    ): array {
        $queryBuilder = $this->createQueryBuilder('a');
        $queryBuilder
            ->innerJoin(
                JobEvent::class,
                'j',
                'WITH',
                'a.signalement = j.signalementId AND a.partner = j.partnerId'
            )
            ->andWhere('j.codeStatus > 399')
            ->andWhere('j.service LIKE :service')
            ->andWhere('j.action LIKE :action')
        ;

        $subQueryBuilder = $this->_em->createQueryBuilder()
            ->select('1')
            ->from(JobEvent::class, 'j2')
            ->where('j2.signalementId = j.signalementId')
            ->andWhere('j2.partnerId = j.partnerId')
            ->andWhere('j2.codeStatus = 200')
            ->andWhere('j2.service LIKE :service')
            ->andWhere('j2.action LIKE :action')
        ;

        if (!empty($partnerTypes)) {
            $subQueryBuilder
                ->andWhere('j2.partnerType IN (:partnerTypes)');
        }

        $queryBuilder
            ->andWhere(
                $queryBuilder->expr()->not(
                    $queryBuilder->expr()->exists($subQueryBuilder->getDQL())
                )
            )
            ->setParameter('service', $service)
            ->setParameter('action', $action)
        ;

        if (!empty($partnerTypes)) {
            $queryBuilder
                ->andWhere('j.partnerType IN (:partnerTypes)')
                ->setParameter('partnerTypes', $partnerTypes);
        }

        return $queryBuilder->getQuery()->getResult();
    }
}
