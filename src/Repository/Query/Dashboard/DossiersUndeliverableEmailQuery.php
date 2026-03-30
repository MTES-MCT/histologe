<?php

namespace App\Repository\Query\Dashboard;

use App\Entity\Affectation;
use App\Entity\EmailDeliveryIssue;
use App\Entity\Enum\AffectationStatus;
use App\Entity\Enum\SignalementStatus;
use App\Entity\Signalement;
use App\Entity\User;
use App\Entity\UserSignalementSubscription;
use App\Service\DashboardTabPanel\TabDossier;
use App\Service\DashboardTabPanel\TabQueryParameters;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;

class DossiersUndeliverableEmailQuery
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    private function buildBaseQb(User $user, ?TabQueryParameters $params): QueryBuilder
    {
        $qb = $this->entityManager->createQueryBuilder();

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
            $existsAffectation = $this->entityManager->createQueryBuilder()
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
            $existsSubscription = $this->entityManager->createQueryBuilder()
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
     * @return array<int, array<string, mixed>>
     */
    public function findSignalements(User $user, ?TabQueryParameters $params): array
    {
        $qb = $this->buildBaseQb($user, $params);

        $qb->select(
            's.uuid AS uuid,
            s.nomOccupant AS nomOccupant,
            s.prenomOccupant AS prenomOccupant,
            s.reference AS reference,
            CONCAT_WS(\', \', s.adresseOccupant, CONCAT(s.cpOccupant, \' \', s.villeOccupant)) AS adresse,
            s.createdAt AS createdAt,
            s.lastSuiviAt AS dernierSuiviAt,
            s.lastSuiviBy AS derniereActionPartenaireNom,
            CASE
                WHEN (FIND_IN_SET(s.mailOccupant, GROUP_CONCAT(DISTINCT edi.email)) > 0
                    AND FIND_IN_SET(s.mailDeclarant, GROUP_CONCAT(DISTINCT edi.email)) > 0)
                    THEN \'Occupant et Tiers\'
                WHEN FIND_IN_SET(s.mailOccupant, GROUP_CONCAT(DISTINCT edi.email)) > 0
                    THEN \'Occupant\'
                WHEN FIND_IN_SET(s.mailDeclarant, GROUP_CONCAT(DISTINCT edi.email)) > 0
                    THEN \'Tiers\'
                ELSE \'\'
            END AS profilNonDeliverable'
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
    public function findIds(User $user, ?TabQueryParameters $params): array
    {
        $qb = $this->buildBaseQb($user, $params);
        $qb->select('s.id')->groupBy('s.id');

        return $qb->getQuery()->getSingleColumnResult();
    }

    public function count(User $user, ?TabQueryParameters $params): int
    {
        $qb = $this->buildBaseQb($user, $params);
        $qb->select('COUNT(DISTINCT s.id)');

        return (int) $qb->getQuery()->getSingleScalarResult();
    }
}
