<?php

namespace App\Repository\Query\Dashboard;

use App\Entity\Enum\SignalementStatus;
use App\Entity\Enum\SuiviCategory;
use App\Entity\Suivi;
use App\Entity\Territory;
use App\Entity\User;
use App\Entity\UserPartner;
use App\Entity\UserSignalementSubscription;
use App\Service\DashboardTabPanel\TabQueryParameters;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator;

class DossiersActiviteRecenteQuery
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    private function getBaseQB(User $user, ?TabQueryParameters $params): QueryBuilder
    {
        $subQb = $this->entityManager->createQueryBuilder()
            ->from(Suivi::class, 'sq')
            ->select('MAX(sq.id)')
            ->where('sq.signalement = suivi.signalement');

        $qb = $this->entityManager->createQueryBuilder()
            ->from(Suivi::class, 'suivi')
            ->innerJoin('suivi.signalement', 'signalement')
            ->andWhere('signalement.statut NOT IN (:excludedStatus)')
            ->andWhere('suivi.createdBy != :user')
            ->andWhere('suivi.category NOT IN (:excludedCategories)')
            ->andWhere('suivi.id = ('.$subQb->getDQL().')')
            ->setParameter('user', $user)
            ->setParameter('excludedStatus', SignalementStatus::excludedStatuses())
            ->setParameter('excludedCategories', [
                SuiviCategory::SIGNALEMENT_STATUS_IS_SYNCHRO,
            ]);

        if ($user->isPartnerAdmin() || $user->isUserPartner()) {
            $qb->innerJoin('signalement.affectations', 'affectation')
            ->andWhere('affectation.partner IN (:partners)')
            ->setParameter('partners', $user->getPartners());
        }

        // Filtrer sur activité récente (< 3 mois)
        $threeMonthsAgo = new \DateTime('-3 months');
        $qb->andWhere('suivi.createdAt >= :threeMonthsAgo')
        ->setParameter('threeMonthsAgo', $threeMonthsAgo);

        if ($params && $params->mesDossiersActiviteRecente && '1' === $params->mesDossiersActiviteRecente) {
            $existsSubscription = $this->entityManager->createQueryBuilder()
                ->select('1')
                ->from(UserSignalementSubscription::class, 'uss')
                ->where('uss.signalement = signalement')
                ->andWhere('uss.user = :currentUser')
                ->getDQL();
            $qb->andWhere($qb->expr()->exists($existsSubscription))
                ->setParameter('currentUser', $user);
        }

        $qb->leftJoin('suivi.createdBy', 'u')
        ->leftJoin(UserPartner::class, 'up', 'WITH', 'up.user = u')
        ->leftJoin('up.partner', 'p', 'WITH', 'p.territory = signalement.territory');

        if ($params?->territoireId) {
            $qb
                ->andWhere('signalement.territory = :territoireId')
                ->setParameter('territoireId', $params->territoireId);
        } elseif (!$user->isSuperAdmin()) {
            $qb
                ->andWhere('signalement.territory IN (:territories)')
                ->setParameter('territories', $user->getPartnersTerritories());
        }

        return $qb;
    }

    /**
     * @return array<int>
     */
    public function findIdsLastSignalementsWithOtherUserSuivi(User $user, ?TabQueryParameters $params): array
    {
        $qb = $this->getBaseQB($user, $params);
        $qb->select('signalement.id')
        ->groupBy('signalement.id');

        return $qb->getQuery()->getSingleColumnResult();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function findLastSignalementsWithOtherUserSuivi(User $user, TabQueryParameters $params, int $limit = 10): array
    {
        $qb = $this->getBaseQB($user, $params);

        $qb->select('
            signalement.reference AS reference,
            signalement.nomOccupant AS nomOccupant,
            signalement.prenomOccupant AS prenomOccupant,
            CONCAT(signalement.adresseOccupant, \' \' , signalement.cpOccupant, \' \' , signalement.villeOccupant) AS adresseOccupant,
            signalement.uuid AS uuid,
            signalement.statut AS statut,
            suivi.createdAt AS suiviCreatedAt,
            suivi.category AS suiviCategory,
            suivi.isPublic AS suiviIsPublic,
            MAX(p.nom) AS derniereActionPartenaireNom,
            u.nom AS derniereActionPartenaireNomAgent,
            u.prenom AS derniereActionPartenairePrenomAgent
        ')->groupBy('signalement.id, suivi.id');

        $qb->orderBy('suivi.createdAt', 'DESC')
        ->setMaxResults($limit);

        return $qb->getQuery()->getArrayResult();
    }

    private function getBaseQBUserActivity(User $user, ?Territory $territory): QueryBuilder
    {
        $subQb = $this->entityManager->createQueryBuilder()
            ->from(Suivi::class, 'sq')
            ->select('MAX(sq.createdAt)')
            ->where('sq.signalement = suivi.signalement')
            ->andWhere('sq.createdBy = :user');

        $qb = $this->entityManager->createQueryBuilder()
            ->from(Suivi::class, 'suivi')
            ->innerJoin('suivi.signalement', 'signalement')
            ->where('suivi.createdBy = :user')
            ->andWhere('signalement.statut NOT IN (:excludedStatus)')
            ->andWhere('suivi.createdAt = ('.$subQb->getDQL().')')
            ->setParameter('user', $user)
            ->setParameter('excludedStatus', SignalementStatus::excludedStatuses());

        if ($user->isPartnerAdmin() || $user->isUserPartner()) {
            $qb->innerJoin('signalement.affectations', 'affectation')
               ->andWhere('affectation.partner IN (:partners)')
               ->setParameter('partners', $user->getPartners());
        }
        if (null !== $territory) {
            $qb->andWhere('signalement.territory = :territory')
                ->setParameter('territory', $territory);
        }

        return $qb;
    }

    /**
     * @return Paginator<array<string, mixed>>
     */
    public function findPaginatedLastSignalementsWithUserSuivi(
        User $user,
        ?Territory $territory,
        int $page,
        int $maxResult,
    ): Paginator {
        $offset = ($page - 1) * $maxResult;

        $qb = $this->getBaseQBUserActivity($user, $territory);
        $qb->orderBy('suivi.createdAt', 'DESC')
            ->setFirstResult($offset)
            ->setMaxResults($maxResult);

        $statutField = 'signalement.statut';

        if ($user->isPartnerAdmin() || $user->isUserPartner()) {
            $statutField = 'affectation.statut';
        }

        $qb->select('
            signalement.reference AS reference,
            signalement.nomOccupant AS nomOccupant,
            signalement.prenomOccupant AS prenomOccupant,
            CONCAT(signalement.adresseOccupant, \' \' , signalement.cpOccupant, \' \' , signalement.villeOccupant) AS adresseOccupant,
            signalement.uuid AS uuid,
            '.$statutField.' AS statut,
            suivi.createdAt AS suiviCreatedAt,
            suivi.category AS suiviCategory,
            suivi.isPublic AS suiviIsPublic,
            (
                SELECT CASE WHEN MAX(s2.createdAt) > suivi.createdAt THEN 1 ELSE 0 END
                FROM '.Suivi::class.' s2
                WHERE s2.signalement = signalement
            ) AS hasNewerSuivi
        ');

        return new Paginator($qb->getQuery(), fetchJoinCollection: false);
    }

    public function countLastSignalementsWithUserSuivi(User $user, ?Territory $territory): int
    {
        $qb = $this->getBaseQBUserActivity($user, $territory);

        return (int) $qb->select('COUNT(DISTINCT signalement.id)')->getQuery()->getSingleScalarResult();
    }
}
