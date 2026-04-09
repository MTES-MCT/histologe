<?php

namespace App\Repository\Query\Dashboard;

use App\Entity\User;
use App\Service\DashboardTabPanel\TabDossier;
use App\Service\DashboardTabPanel\TabQueryParameters;
use App\Utils\Address\CommuneHelper;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\ORM\EntityManagerInterface;

class DossiersSansSuivisPartenaireQuery
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly DossiersAvecRelanceSansReponseQuery $dossiersAvecRelanceSansReponseQuery,
        private readonly DossiersSuivisUsagerQuery $dossiersSuivisUsagerQuery,
    ) {
    }

    /**
     * @return array<int, array<string, array<int|string|null>|int|string>|string>
     */
    private function getBaseSql(
        User $user,
        TabQueryParameters $params,
        ?bool $withJoins = false,
    ): array {
        $excludedIds = array_merge(
            $this->dossiersAvecRelanceSansReponseQuery->getSignalementsId($user),
            $this->dossiersSuivisUsagerQuery->getSignalementsIdWithSuivisUsagerOrPoursuiteWithAskFeedbackBefore($user, $params)
        );
        $categories = [
            'MESSAGE_PARTNER',
            'SIGNALEMENT_EDITED_BO',
            'SIGNALEMENT_IS_ACTIVE',
            'SIGNALEMENT_IS_REOPENED',
            'INTERVENTION_IS_CREATED',
            'INTERVENTION_IS_CANCELED',
            'INTERVENTION_IS_ABORTED',
            'INTERVENTION_HAS_CONCLUSION',
            'INTERVENTION_HAS_CONCLUSION_EDITED',
            'INTERVENTION_IS_RESCHEDULED',
            'INTERVENTION_IS_DONE',
            'INTERVENTION_CONTROLE_IS_CREATED',
            'INTERVENTION_CONTROLE_IS_RESCHEDULED',
            'INTERVENTION_CONTROLE_IS_DONE',
            'INTERVENTION_ARRETE_IS_CREATED',
            'INTERVENTION_ARRETE_IS_RESCHEDULED',
            'NEW_DOCUMENT',
            'AFFECTATION_IS_CLOSED',
        ];

        $paramsToBind = [];
        $types = [];
        $categoryList = "'".implode("','", $categories)."'";
        $sql = <<<SQL
            FROM signalement si
            INNER JOIN suivi s ON s.signalement_id = si.id
        SQL;
        if ($withJoins) {
            $sql .= <<<SQL
                LEFT JOIN user u ON u.id = s.created_by_id
                LEFT JOIN user_partner up ON up.user_id = u.id
                LEFT JOIN partner p ON p.id = up.partner_id
            SQL;
        }
        if ($user->isPartnerAdmin() || $user->isUserPartner() || ($params->partners && count($params->partners) > 0)) {
            $sql .= <<<SQL
                LEFT JOIN affectation aff ON aff.signalement_id = si.id
            SQL;
        }

        $sql .= <<<SQL
            WHERE s.category IN ($categoryList)
            AND s.created_at = (
                SELECT MAX(s2.created_at)
                FROM suivi s2
                WHERE s2.signalement_id = si.id
                AND s2.category IN ($categoryList)
            )
            AND s.created_at < :dateLimit
            AND si.statut = 'ACTIVE'
        SQL;

        if ($user->isPartnerAdmin() || $user->isUserPartner()) {
            $sql .= ' AND aff.partner_id IN (:partners)';
            $sql .= ' AND aff.statut IN (\'EN_COURS\', \'NOUVEAU\')';
            $paramsToBind['partners'] = array_map(
                static fn ($partner) => $partner->getId(),
                $user->getPartners()->toArray()
            );
            $types['partners'] = ArrayParameterType::INTEGER;
        }

        if ($params->territoireId) {
            $sql .= ' AND si.territory_id = '.$params->territoireId;
        } elseif (!$user->isSuperAdmin()) {
            $sql .= ' AND si.territory_id IN ('.implode(',', array_keys($user->getPartnersTerritories())).')';
        }

        if ($params->mesDossiersAverifier && '1' === $params->mesDossiersAverifier) {
            $sql .= ' AND EXISTS (
            SELECT 1
            FROM user_signalement_subscription uss
            WHERE uss.signalement_id = si.id
              AND uss.user_id = '.$user->getId().'
        )';
        }

        $paramsToBind['dateLimit'] = (new \DateTimeImmutable('-60 days'))->format('Y-m-d H:i:s');

        if ($params->partners && count($params->partners) > 0) {
            $sql .= ' AND aff.partner_id IN (:partnersId)';
            $paramsToBind['partnersId'] = $params->partners;
            $types['partnersId'] = ArrayParameterType::INTEGER;
        }

        if ($params->queryCommune) {
            $listCity = [$params->queryCommune];
            if (isset(CommuneHelper::COMMUNES_ARRONDISSEMENTS[$params->queryCommune])) {
                $listCity = array_merge($listCity, CommuneHelper::COMMUNES_ARRONDISSEMENTS[$params->queryCommune]);
            }
            $sql .= ' AND (si.cp_occupant IN (:cities) OR si.ville_occupant IN (:cities))';
            $paramsToBind['cities'] = $listCity;
            $types['cities'] = ArrayParameterType::STRING;
        }

        if (!empty($excludedIds)) {
            $sql .= ' AND si.id NOT IN (:excludedIds)';
            $paramsToBind['excludedIds'] = $excludedIds;
            $types['excludedIds'] = ArrayParameterType::INTEGER;
        }

        return [$sql, $paramsToBind, $types];
    }

    public function countSignalements(User $user, ?TabQueryParameters $params): int
    {
        $conn = $this->entityManager->getConnection();

        $sql = 'SELECT COUNT(DISTINCT si.id) ';
        /** @var string $sqlPrincipal */
        /** @var array<mixed> $paramsToBind */
        /** @var array<mixed> $types */
        [$sqlPrincipal, $paramsToBind, $types] = $this->getBaseSql($user, $params);
        $sql .= $sqlPrincipal;

        return (int) $conn->executeQuery($sql, $paramsToBind, $types)->fetchOne();
    }

    /**
     * @return int[]
     */
    public function getSignalementsId(User $user, TabQueryParameters $params): array
    {
        $conn = $this->entityManager->getConnection();

        $sql = 'SELECT DISTINCT si.id ';
        /** @var string $sqlPrincipal */
        /** @var array<mixed> $paramsToBind */
        /** @var array<mixed> $types */
        [$sqlPrincipal, $paramsToBind, $types] = $this->getBaseSql($user, $params);
        $sql .= $sqlPrincipal;

        return array_map('intval', $conn->executeQuery($sql, $paramsToBind, $types)->fetchFirstColumn());
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function findSignalements(User $user, ?TabQueryParameters $params): array
    {
        $conn = $this->entityManager->getConnection();

        $sql = <<<SQL
        SELECT
            si.id,
            si.uuid AS uuid,
            si.reference AS reference,
            si.nom_occupant AS nomOccupant,
            si.prenom_occupant AS prenomOccupant,
            CONCAT_WS(', ', si.adresse_occupant, CONCAT(si.cp_occupant, ' ', si.ville_occupant)) AS adresse,
            MAX(s.created_at) AS dernierSuiviAt,
            DATEDIFF(CURRENT_DATE(), MAX(s.created_at)) AS nbJoursDepuisDernierSuivi,
            MAX(s.category) AS suiviCategory,
            MAX(p.nom) AS derniereActionPartenaireNom,
            MAX(u.nom) AS derniereActionPartenaireNomAgent,
            MAX(u.prenom) AS derniereActionPartenairePrenomAgent
        SQL;
        /** @var string $sqlPrincipal */
        /** @var array<mixed> $paramsToBind */
        /** @var array<mixed> $types */
        [$sqlPrincipal, $paramsToBind, $types] = $this->getBaseSql($user, $params, true);
        $sql .= $sqlPrincipal;
        $sql .= ' GROUP BY si.id, si.uuid, si.reference, si.nom_occupant, si.prenom_occupant, si.adresse_occupant, si.cp_occupant, si.ville_occupant';

        if ($params && in_array($params->sortBy, ['createdAt'], true)
            && in_array($params->orderBy, ['ASC', 'DESC', 'asc', 'desc'], true)
        ) {
            $sql .= ' ORDER BY MAX(s.created_at) '.$params->orderBy;
        } else {
            $sql .= ' ORDER BY MAX(s.created_at) ASC';
        }

        $sql .= ' LIMIT '.TabDossier::MAX_ITEMS_LIST;

        return $conn->executeQuery($sql, $paramsToBind, $types)->fetchAllAssociative();
    }
}
