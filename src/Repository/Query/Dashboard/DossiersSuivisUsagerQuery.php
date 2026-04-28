<?php

namespace App\Repository\Query\Dashboard;

use App\Entity\Affectation;
use App\Entity\Enum\AffectationStatus;
use App\Entity\Enum\SignalementStatus;
use App\Entity\Enum\SuiviCategory;
use App\Entity\Notification;
use App\Entity\Signalement;
use App\Entity\Suivi;
use App\Entity\User;
use App\Entity\UserSignalementSubscription;
use App\Service\DashboardTabPanel\Kpi\CountDossiersMessagesUsagers;
use App\Service\DashboardTabPanel\TabDossier;
use App\Service\DashboardTabPanel\TabQueryParameters;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;

class DossiersSuivisUsagerQuery
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * @param SuiviCategory[] $categories
     */
    private function getBaseQB(
        User $user,
        ?TabQueryParameters $params,
        array $categories,
        bool $onlyLastSuivi = true,
        bool $forCount = false,
    ): QueryBuilder {
        $qb = $this->entityManager->createQueryBuilder()->from(Signalement::class, 'signalement');

        if ($onlyLastSuivi) {
            $qb->innerJoin('signalement.lastSuivi', 's', 'WITH', 's.category IN (:categories)');
        } else {
            $qb->innerJoin('signalement.suivis', 's', 'WITH', 's.category IN (:categories)');
        }

        $qb->setParameter('categories', $categories);

        if (!$forCount) {
            $qb->leftJoin('s.createdBy', 'user');
        }

        if ($params?->territoireId) {
            $qb
                ->andWhere('signalement.territory = :territoireId')
                ->setParameter('territoireId', $params->territoireId);
        } elseif (!$user->isSuperAdmin()) {
            $qb
                ->andWhere('signalement.territory IN (:territories)')
                ->setParameter('territories', $user->getPartnersTerritories());
        }

        if ($user->isPartnerAdmin() || $user->isUserPartner()) {
            $existsAffectation = $this->entityManager->createQueryBuilder()
                ->select('1')
                ->from(Affectation::class, 'af')
                ->where('af.signalement = signalement')
                ->andWhere('af.partner IN (:partners)')
                ->andWhere('af.statut = :affectationStatus')
                ->getDQL();
            $qb->andWhere($qb->expr()->exists($existsAffectation))
                ->setParameter('partners', $user->getPartners())
                ->setParameter('affectationStatus', AffectationStatus::ACCEPTED);
        }

        if ($params?->mesDossiersMessagesUsagers && '1' === $params->mesDossiersMessagesUsagers) {
            $existsSubscription = $this->entityManager->createQueryBuilder()
                ->select('1')
                ->from(UserSignalementSubscription::class, 'uss')
                ->where('uss.signalement = signalement')
                ->andWhere('uss.user = :currentUser')
                ->getDQL();
            $qb->andWhere($qb->expr()->exists($existsSubscription))
                ->setParameter('currentUser', $user);
        }

        return $qb;
    }

    private function addSelectAndOrder(
        QueryBuilder $qb,
        ?TabQueryParameters $params,
        bool $countOnly = false,
        bool $idsOnly = false,
    ): QueryBuilder {
        if ($countOnly) {
            $qb->select('COUNT(DISTINCT signalement.id)');

            return $qb;
        }

        if ($idsOnly) {
            $qb->select('DISTINCT signalement.id');

            return $qb;
        }

        $qb->select(
            'signalement.uuid AS uuid',
            'signalement.nomOccupant AS nomOccupant',
            'signalement.prenomOccupant AS prenomOccupant',
            'signalement.reference AS reference',
            "CONCAT_WS(', ', signalement.adresseOccupant, CONCAT(signalement.cpOccupant, ' ', signalement.villeOccupant)) AS adresse",
            'MAX(s.createdAt) AS messageAt',
            'DATE_DIFF(CURRENT_DATE(), MAX(s.createdAt)) AS messageDaysAgo',
            'signalement.closedAt AS clotureAt',
            'MAX(user.nom) AS messageSuiviByNom',
            'MAX(user.prenom) AS messageSuiviByPrenom',
            "CASE
                WHEN MAX(user.email) = signalement.mailOccupant THEN 'OCCUPANT'
                WHEN MAX(user.email) = signalement.mailDeclarant THEN 'TIERS DECLARANT'
                ELSE 'OCCUPANT OU DECLARANT'
            END AS messageByProfileDeclarant"
        );

        if ($params && in_array($params->sortBy, ['createdAt'], true)
            && in_array($params->orderBy, ['ASC', 'DESC', 'asc', 'desc'], true)
        ) {
            $qb->orderBy('messageAt', $params->orderBy);
        } else {
            $qb->orderBy('messageAt', 'ASC');
        }

        $qb->setMaxResults(TabDossier::MAX_ITEMS_LIST);
        $qb->groupBy('signalement.id');

        return $qb;
    }

    private function addFilterNoPreviousAskFeedback(QueryBuilder $qb): QueryBuilder
    {
        // on vérifie que l'avant-dernier suivi n'est pas une demande de feedback
        $qb->andWhere('NOT EXISTS (
            SELECT 1
            FROM '.Suivi::class.' s3
            WHERE s3.signalement = signalement
            AND s3.category = :askFeedbackCategory
            AND s3.createdAt = (
                SELECT MAX(s4.createdAt)
                FROM '.Suivi::class.' s4
                WHERE s4.signalement = signalement
                AND s4.createdAt < s.createdAt
            )
        )')
        ->setParameter('askFeedbackCategory', SuiviCategory::ASK_FEEDBACK_SENT)
        ->andWhere('signalement.statut = :statut')
        ->setParameter('statut', SignalementStatus::ACTIVE);

        return $qb;
    }

    /**
     * @return array<int>
     */
    public function getSignalementsIdWithSuivisUsagersWithoutAskFeedbackBefore(User $user, ?TabQueryParameters $params): array
    {
        $qb = $this->getBaseQB($user, $params, [SuiviCategory::MESSAGE_USAGER, SuiviCategory::MESSAGE_USAGER_POST_CLOTURE], true, true);
        $qb = $this->addFilterNoPreviousAskFeedback($qb);
        $qb = $this->addSelectAndOrder($qb, $params, false, true);

        return $qb->getQuery()->getSingleColumnResult();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function findSuivisUsagersWithoutAskFeedbackBefore(User $user, ?TabQueryParameters $params): array
    {
        $qb = $this->getBaseQB($user, $params, [SuiviCategory::MESSAGE_USAGER, SuiviCategory::MESSAGE_USAGER_POST_CLOTURE], true, false);
        $qb = $this->addFilterNoPreviousAskFeedback($qb);
        $qb = $this->addSelectAndOrder($qb, $params);

        return $qb->getQuery()->getResult();
    }

    public function countSuivisUsagersWithoutAskFeedbackBefore(User $user, ?TabQueryParameters $params): int
    {
        $qb = $this->getBaseQB($user, $params, [SuiviCategory::MESSAGE_USAGER, SuiviCategory::MESSAGE_USAGER_POST_CLOTURE], true, true);
        $qb = $this->addFilterNoPreviousAskFeedback($qb);
        $qb = $this->addSelectAndOrder($qb, $params, true);

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    private function addFilterNotificationNotSeen(
        QueryBuilder $qb,
        User $user,
    ): QueryBuilder {
        $qb->innerJoin(
            Notification::class,
            'n',
            'WITH',
            'n.suivi = s AND n.user = :currentUser'
        )
            ->andWhere('n.seenAt IS NULL')
            ->setParameter('currentUser', $user)
            ->andWhere('n.deleted = :deleted')
            ->setParameter('deleted', false);

        $qb->andWhere('signalement.statut = :statut')
            ->setParameter('statut', SignalementStatus::CLOSED);

        return $qb;
    }

    /**
     * @return array<int>
     */
    public function getSignalementsIdWithSuivisPostCloture(User $user, ?TabQueryParameters $params): array
    {
        $qb = $this->getBaseQB($user, $params, [SuiviCategory::MESSAGE_USAGER_POST_CLOTURE], false, true);
        $qb = $this->addFilterNotificationNotSeen($qb, $user);
        $qb = $this->addSelectAndOrder($qb, $params, false, true);

        return $qb->getQuery()->getSingleColumnResult();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function findSuivisPostCloture(User $user, ?TabQueryParameters $params): array
    {
        $qb = $this->getBaseQB($user, $params, [SuiviCategory::MESSAGE_USAGER_POST_CLOTURE], false, false);
        $qb = $this->addFilterNotificationNotSeen($qb, $user);
        $qb = $this->addSelectAndOrder($qb, $params);

        return $qb->getQuery()->getResult();
    }

    public function countSuivisPostCloture(User $user, ?TabQueryParameters $params): int
    {
        $qb = $this->getBaseQB($user, $params, [SuiviCategory::MESSAGE_USAGER_POST_CLOTURE], false, true);
        $qb = $this->addFilterNotificationNotSeen($qb, $user);
        $qb = $this->addSelectAndOrder($qb, $params, true);

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    private function addFilterAskFeedbackBeforeAndNoPublicAfter(QueryBuilder $qb): QueryBuilder
    {
        // TODO : essayer d'améliorer les perfs
        // une demande de feedback avant le message usager ou demande poursuite procedure
        // mais pas de suivi public entre les deux
        $qb->andWhere('EXISTS (
            SELECT 1
            FROM '.Suivi::class.' s_ask
            WHERE s_ask.signalement = signalement
              AND s_ask.category = :askFeedbackCategory
              AND s_ask.createdAt < s.createdAt
              AND NOT EXISTS (
                  SELECT 1
                  FROM '.Suivi::class.' s_pub_before
                  WHERE s_pub_before.signalement = signalement
                    AND s_pub_before.isVisibleForUsager = 1
                    AND s_pub_before.createdAt > s_ask.createdAt
                    AND s_pub_before.createdAt < s.createdAt
              )
        )');

        // aucun suivi public depuis ce message usager ou demande poursuite procedure
        $qb->andWhere('NOT EXISTS (
            SELECT 1
            FROM '.Suivi::class.' s_pub
            WHERE s_pub.signalement = signalement
              AND s_pub.isVisibleForUsager = true
              AND s_pub.category NOT IN (:usagerCategory, :poursuiteCategory)
              AND s_pub.createdAt > s.createdAt
        )');
        $qb->andWhere('signalement.statut = :statut')
           ->setParameter('statut', SignalementStatus::ACTIVE)
           ->setParameter('askFeedbackCategory', SuiviCategory::ASK_FEEDBACK_SENT)
           ->setParameter('usagerCategory', SuiviCategory::MESSAGE_USAGER)
           ->setParameter('poursuiteCategory', SuiviCategory::DEMANDE_POURSUITE_PROCEDURE);

        return $qb;
    }

    /**
     * @return array<int>
     */
    public function getSignalementsIdWithSuivisUsagerOrPoursuiteWithAskFeedbackBefore(User $user, ?TabQueryParameters $params): array
    {
        $qb = $this->getBaseQB($user, $params, [SuiviCategory::MESSAGE_USAGER, SuiviCategory::MESSAGE_USAGER_POST_CLOTURE, SuiviCategory::DEMANDE_POURSUITE_PROCEDURE], false, true);
        $qb = $this->addFilterAskFeedbackBeforeAndNoPublicAfter($qb);
        $qb = $this->addSelectAndOrder($qb, $params, false, true);

        return $qb->getQuery()->getSingleColumnResult();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function findSuivisUsagerOrPoursuiteWithAskFeedbackBefore(User $user, ?TabQueryParameters $params): array
    {
        $qb = $this->getBaseQB($user, $params, [SuiviCategory::MESSAGE_USAGER, SuiviCategory::MESSAGE_USAGER_POST_CLOTURE, SuiviCategory::DEMANDE_POURSUITE_PROCEDURE], false, false);
        $qb = $this->addFilterAskFeedbackBeforeAndNoPublicAfter($qb);
        $qb = $this->addSelectAndOrder($qb, $params);

        return $qb->getQuery()->getResult();
    }

    public function countSuivisUsagerOrPoursuiteWithAskFeedbackBefore(User $user, ?TabQueryParameters $params): int
    {
        $qb = $this->getBaseQB($user, $params, [SuiviCategory::MESSAGE_USAGER, SuiviCategory::MESSAGE_USAGER_POST_CLOTURE, SuiviCategory::DEMANDE_POURSUITE_PROCEDURE], false, true);
        $qb = $this->addFilterAskFeedbackBeforeAndNoPublicAfter($qb);
        $qb = $this->addSelectAndOrder($qb, $params, true);

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    public function countAllMessagesUsagers(User $user, ?TabQueryParameters $params): CountDossiersMessagesUsagers
    {
        return new CountDossiersMessagesUsagers(
            $this->countSuivisUsagersWithoutAskFeedbackBefore($user, $params),
            ($user->isSuperAdmin() || $user->isTerritoryAdmin()) ? $this->countSuivisPostCloture($user, $params) : 0,
            $this->countSuivisUsagerOrPoursuiteWithAskFeedbackBefore($user, $params)
        );
    }
}
