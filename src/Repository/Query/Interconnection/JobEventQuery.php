<?php

namespace App\Repository\Query\Interconnection;

use App\Entity\Enum\InterfacageType;
use App\Entity\JobEvent;
use App\Entity\Partner;
use App\Entity\Signalement;
use App\Service\ListFilters\SearchInterconnexion;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\QueryBuilder;

class JobEventQuery
{
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    /**
     * @throws \DateMalformedStringException
     */
    private function createJobEventByTerritoryQueryBuilder(
        int $dayPeriod,
        SearchInterconnexion $searchInterconnexion,
        bool $count = false,
    ): QueryBuilder {
        $qb = $this
            ->entityManager->createQueryBuilder()
            ->from(JobEvent::class, 'j')
            ->where('j.createdAt >= :date_limit')
            ->setParameter('date_limit', new \DateTimeImmutable('-'.$dayPeriod.' days'));

        $needsPartnerJoin =
            null !== $searchInterconnexion->getTerritory()
            || null !== $searchInterconnexion->getPartner()
            || !$count;

        $needsSignalementJoin =
            null !== $searchInterconnexion->getReference()
            || !$count;

        if ($needsPartnerJoin) {
            $joinType = ($searchInterconnexion->getTerritory() || $searchInterconnexion->getPartner())
                ? 'innerJoin'
                : 'leftJoin';

            $qb->{$joinType}(Partner::class, 'p', 'ON', 'p.id = j.partnerId');
        }

        if ($needsSignalementJoin) {
            $joinType = $searchInterconnexion->getReference()
                ? 'innerJoin'
                : 'leftJoin';

            $qb->{$joinType}(Signalement::class, 's', 'ON', 's.id = j.signalementId');
        }

        if ($searchInterconnexion->getTerritory()) {
            $qb->andWhere('p.territory = :territory')->setParameter('territory', $searchInterconnexion->getTerritory());
        }
        if ($searchInterconnexion->getPartner()) {
            $qb->andWhere('p.id = :partnerId')->setParameter('partnerId', $searchInterconnexion->getPartner()->getId());
        }
        if ($searchInterconnexion->getStatus()) {
            if ('failed' === $searchInterconnexion->getStatus()) {
                $qb->andWhere('j.status = :status')->setParameter('status', JobEvent::STATUS_FAILED);
            } elseif ('warning' === $searchInterconnexion->getStatus()) {
                $qb
                    ->andWhere('j.status = :status')
                    ->andWhere('(
                        j.response IS NULL
                        OR j.response = \'{}\'
                        OR j.response = \'[]\'
                        OR LENGTH(TRIM(j.response)) = 0
                    )')
                    ->setParameter('status', JobEvent::STATUS_SUCCESS);
            } else {
                $qb->andWhere('j.status = :status')
                    ->andWhere('j.response IS NOT NULL')
                    ->andWhere('LENGTH(TRIM(j.response)) > 0')
                    ->andWhere('j.response != \'[]\'')
                    ->andWhere('j.response != \'{}\'')
                    ->setParameter('status', $searchInterconnexion->getStatus())
                ;
            }
        }
        if ($searchInterconnexion->getAction()) {
            $qb->andWhere('j.action = :action')->setParameter('action', $searchInterconnexion->getAction());
        }
        if ($searchInterconnexion->getReference()) {
            $qb->andWhere('s.reference = :reference')->setParameter('reference', $searchInterconnexion->getReference());
        }

        if ($searchInterconnexion->getService()) {
            $qb->andWhere('j.service = :service')->setParameter('service', $searchInterconnexion->getService());
        }

        if ($searchInterconnexion->getPartnerType()) {
            $qb->andWhere('j.partnerType = :partnerType')->setParameter('partnerType', $searchInterconnexion->getPartnerType()->value);
        }

        if ($searchInterconnexion->showOnlyDataErrors()) {
            $qb->andWhere('j.isOperationalError = 1 OR j.status = :status_success')
                ->setParameter('status_success', JobEvent::STATUS_SUCCESS);
        }

        return $qb;
    }

    /**
     * @return array<int, array<string, mixed>>
     *
     * @throws \DateMalformedStringException
     */
    public function findLastByTerritory(
        int $dayPeriod,
        SearchInterconnexion $searchInterconnexion,
        int $limit,
        int $offset,
    ): array {
        /** @var QueryBuilder $qb */
        $qb = $this->createJobEventByTerritoryQueryBuilder($dayPeriod, $searchInterconnexion);
        $qb->select(
            'j.createdAt',
            'p.id',
            'p.nom',
            's.reference',
            's.uuid',
            'j.status',
            'j.service',
            'j.action',
            'j.codeStatus',
            'j.message',
            'j.response',
            'j.attachmentsCount',
            'j.attachmentsSize'
        );

        if (!empty($searchInterconnexion->getOrderType())) {
            [$orderField, $orderDirection] = explode('-', $searchInterconnexion->getOrderType());
            $qb->orderBy($orderField, $orderDirection);
        } else {
            $qb->orderBy('j.createdAt', 'DESC');
        }

        $qb->setMaxResults($limit)->setFirstResult($offset);

        return $qb->getQuery()->getArrayResult();
    }

    /**
     * @throws NonUniqueResultException
     * @throws NoResultException
     * @throws \DateMalformedStringException
     */
    public function countByTerritory(
        int $dayPeriod,
        SearchInterconnexion $searchInterconnexion,
    ): int {
        $qb = $this->createJobEventByTerritoryQueryBuilder($dayPeriod, $searchInterconnexion, true);
        $qb->select('COUNT(j.id)');

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * @return array<string, mixed>|null
     *
     * @throws NonUniqueResultException
     */
    public function findLastEsaboraByPartner(
        Partner $partner,
    ): ?array {
        return $this->entityManager->createQueryBuilder()
            ->from(JobEvent::class, 'j')
            ->select('MAX(j.createdAt) AS last_event')
            ->innerJoin(Partner::class, 'p', 'ON', 'p.id = j.partnerId')
            ->where('p.id = :partner')->setParameter('partner', $partner->getId())
            ->andWhere('j.service = :service')
            ->setParameter('service', InterfacageType::ESABORA->value)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @return array<string, int>
     */
    public function getReportEsaboraAction(string ...$actions): array
    {
        $qb = $this->entityManager->createQueryBuilder();
        $qb->from(JobEvent::class, 'j')
            ->select([
                'SUM(CASE WHEN j.status = :status_success THEN 1 ELSE 0 END) AS success_count',
                'SUM(CASE WHEN j.status = :status_failed THEN 1 ELSE 0 END) AS failed_count',
            ])
            ->andWhere('j.action IN (:actions)')
            ->andWhere('DATE(j.createdAt) = :today')
            ->setParameter('actions', $actions)
            ->setParameter('today', (new \DateTimeImmutable())->format('Y-m-d'))
            ->setParameter('status_success', JobEvent::STATUS_SUCCESS)
            ->setParameter('status_failed', JobEvent::STATUS_FAILED);

        $result = $qb->getQuery()->getSingleResult();

        return [
            'success_count' => (int) ($result['success_count'] ?? 0),
            'failed_count' => (int) ($result['failed_count'] ?? 0),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function findSyncStatusesForSignalement(Signalement $signalement): array
    {
        // Sous-requête corrélée : recherche un événement terminal plus récent
        // pour le même signalement et le même partenaire.
        $newerEventQb = $this->entityManager->createQueryBuilder();
        $newerEventQb->select('1')
            ->from(JobEvent::class, 'newer_event')
            ->where('newer_event.signalementId = j.signalementId')
            ->andWhere('newer_event.partnerId = j.partnerId')
            ->andWhere('newer_event.status IN (:terminalStatuses)')
            ->andWhere('(
                newer_event.createdAt > j.createdAt
                OR (newer_event.createdAt = j.createdAt AND newer_event.id > j.id)
            )');

        // Sélectionne les échecs qui ne sont suivis d'aucun autre événement terminal.
        // Si un succès ou un nouvel échec existe, l'événement courant est ignoré.
        $qb = $this->entityManager->createQueryBuilder();
        $qb->from(JobEvent::class, 'j')
            ->select([
                'j.partnerId AS partner_id',
                'j.action AS last_job_event_action',
                'j.response AS last_job_event_response',
            ])
            ->where('j.signalementId = :signalementId')
            ->andWhere('j.status = :failedStatus')
            ->andWhere($qb->expr()->not($qb->expr()->exists($newerEventQb->getDQL())))
            ->setParameter('signalementId', $signalement->getId())
            ->setParameter('failedStatus', JobEvent::STATUS_FAILED)
            ->setParameter('terminalStatuses', [JobEvent::STATUS_FAILED, JobEvent::STATUS_SUCCESS]);

        $results = $qb->getQuery()->getArrayResult();
        $syncStatuses = [];
        foreach ($results as $result) {
            $syncStatuses[$result['partner_id']] = [
                'last_job_event_action' => $result['last_job_event_action'],
                'last_job_event_response' => $result['last_job_event_response'],
            ];
        }

        return $syncStatuses;
    }
}
