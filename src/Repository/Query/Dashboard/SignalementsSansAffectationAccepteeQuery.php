<?php

namespace App\Repository\Query\Dashboard;

use App\Entity\User;
use App\Service\DashboardTabPanel\TabDossier;
use App\Service\DashboardTabPanel\TabQueryParameters;
use App\Utils\Address\CommuneHelper;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\ORM\EntityManagerInterface;

class SignalementsSansAffectationAccepteeQuery
{
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    /**
     * @return array<int, array<string, array<int|string|null>|int|string>|string>
     */
    private function getBaseSql(
        User $user,
        TabQueryParameters $params,
    ): array {
        $paramsToBind = [];
        $types = [];
        $sql = <<<SQL
            FROM signalement s
            INNER JOIN affectation a ON a.signalement_id = s.id
            LEFT JOIN affectation a_active 
                ON a_active.signalement_id = s.id
                AND ( a_active.statut = 'EN_COURS' OR a_active.statut = 'FERME' )
            WHERE s.statut = 'ACTIVE'
            AND a_active.id IS NULL
        SQL;

        if ($params->territoireId) {
            $sql .= ' AND s.territory_id = '.$params->territoireId;
        } elseif (!$user->isSuperAdmin()) {
            $sql .= ' AND s.territory_id IN ('.implode(',', array_keys($user->getPartnersTerritories())).')';
        }

        if ($params->mesDossiersAverifier && '1' === $params->mesDossiersAverifier) {
            $sql .= ' AND EXISTS (
            SELECT 1
            FROM user_signalement_subscription uss
            WHERE uss.signalement_id = s.id
              AND uss.user_id = '.$user->getId().'
        )';
        }

        if ($params->partners && count($params->partners) > 0) {
            $sql .= ' AND EXISTS (
                SELECT 1
                FROM affectation af
                WHERE af.signalement_id = s.id
                AND af.partner_id IN (:partnersId)
            )';
            $paramsToBind['partnersId'] = $params->partners;
            $types['partnersId'] = ArrayParameterType::INTEGER;
        }

        if ($params->queryCommune) {
            $listCity = [$params->queryCommune];
            if (isset(CommuneHelper::COMMUNES_ARRONDISSEMENTS[$params->queryCommune])) {
                $listCity = array_merge($listCity, CommuneHelper::COMMUNES_ARRONDISSEMENTS[$params->queryCommune]);
            }
            $sql .= ' AND (s.cp_occupant IN (:cities) OR s.ville_occupant IN (:cities))';
            $paramsToBind['cities'] = $listCity;
            $types['cities'] = ArrayParameterType::STRING;
        }

        return [$sql, $paramsToBind, $types];
    }

    public function countSignalements(User $user, ?TabQueryParameters $params): int
    {
        $conn = $this->entityManager->getConnection();

        $sql = 'SELECT COUNT(DISTINCT s.id) ';
        /** @var string $sqlPrincipal */
        /** @var array<mixed> $paramsToBind */
        /** @var array<mixed> $types */
        [$sqlPrincipal, $paramsToBind, $types] = $this->getBaseSql($user, $params);
        $sql .= $sqlPrincipal;

        return (int) $conn->executeQuery($sql, $paramsToBind, $types)->fetchOne();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function findSignalements(User $user, ?TabQueryParameters $params): array
    {
        $conn = $this->entityManager->getConnection();

        $sql = <<<SQL
        SELECT DISTINCT
            s.id,
            s.uuid AS uuid,
            s.reference AS reference,
            s.nom_occupant AS nomOccupant,
            s.prenom_occupant AS prenomOccupant,
            CONCAT_WS(', ', s.adresse_occupant, CONCAT(s.cp_occupant, ' ', s.ville_occupant)) AS adresse,
            CASE
                WHEN s.is_logement_social = true THEN 'PUBLIC'
                ELSE 'PRIVÉ'
            END AS parc,
            COUNT(a.id) AS nbAffectations,
            MAX(a.created_at) AS lastAffectationAt
        SQL;
        /** @var string $sqlPrincipal */
        /** @var array<mixed> $paramsToBind */
        /** @var array<mixed> $types */
        [$sqlPrincipal, $paramsToBind, $types] = $this->getBaseSql($user, $params);
        $sql .= $sqlPrincipal;

        $sql .= ' GROUP BY
            s.id,
            s.uuid,
            s.reference,
            s.nom_occupant,
            s.prenom_occupant,
            s.adresse_occupant,
            s.cp_occupant,
            s.ville_occupant,
            s.is_logement_social
        ';

        if ($params && in_array($params->sortBy, ['affectedAt', 'nomOccupant'], true)
            && in_array($params->orderBy, ['ASC', 'DESC', 'asc', 'desc'], true)
        ) {
            if ('affectedAt' === $params->sortBy) {
                $sql .= ' ORDER BY MAX(a.created_at) '.$params->orderBy;
            } else {
                $sql .= ' ORDER BY s.nom_occupant '.$params->orderBy;
            }
        } else {
            $sql .= ' ORDER BY MAX(a.created_at) ASC';
        }

        $sql .= ' LIMIT '.TabDossier::MAX_ITEMS_LIST;

        return $conn->executeQuery($sql, $paramsToBind, $types)->fetchAllAssociative();
    }
}
