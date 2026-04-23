<?php

namespace App\Repository\Query\Dashboard;

use App\Entity\User;
use App\Service\DashboardTabPanel\TabDossier;
use App\Service\DashboardTabPanel\TabQueryParameters;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Exception;
use Doctrine\ORM\EntityManagerInterface;

class DossiersAvecRelanceSansReponseQuery
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    private function getBaseSql(?array $territoriesIds): string
    {
        $clauseTerritoriesSi2 = '';
        $clauseTerritoriesSi3 = '';
        $clauseTerritoriesSi = '';
        if (null !== $territoriesIds) {
            $clauseTerritoriesSi2 = ' AND si2.territory_id IN (:territories_ids) ';
            $clauseTerritoriesSi3 = ' AND si3.territory_id IN (:territories_ids) ';
            $clauseTerritoriesSi = ' AND si.territory_id IN (:territories_ids) ';
        }

        // TODO : à changer
        return <<<SQL
            FROM (
                SELECT
                    s.signalement_id,
                    MIN(s.created_at) AS first_relance_at,
                    COUNT(*) AS nb_relances
                FROM suivi s
                WHERE s.category = 'ASK_FEEDBACK_SENT'
                  AND EXISTS (
                    SELECT 1 FROM signalement si2
                    WHERE si2.id = s.signalement_id
                      AND si2.statut = 'ACTIVE'
                      $clauseTerritoriesSi2
                  )
                GROUP BY s.signalement_id
                HAVING COUNT(*) >= 3
            ) relances_usager
            INNER JOIN signalement si ON si.id = relances_usager.signalement_id
            INNER JOIN (
                SELECT
                    s.signalement_id,
                    MAX(s.created_at) AS shared_usager_at,
                    MAX(s.type) AS type
                FROM suivi s
                WHERE s.is_public = 1
                  AND EXISTS (
                    SELECT 1 FROM signalement si3
                    WHERE si3.id = s.signalement_id
                      AND si3.statut = 'ACTIVE'
                      $clauseTerritoriesSi3
                  )
                GROUP BY s.signalement_id
            ) last_usager_suivi ON last_usager_suivi.signalement_id = si.id
            WHERE
                si.statut = 'ACTIVE'
                AND NOT EXISTS (
                    SELECT 1
                    FROM suivi s2
                    WHERE s2.signalement_id = relances_usager.signalement_id
                      AND s2.type = 2
                      AND s2.created_at > relances_usager.first_relance_at
                )
                $clauseTerritoriesSi
        SQL;
    }

    /**
     * @return TabDossier[]
     *
     * @throws \DateMalformedStringException
     * @throws Exception
     */
    public function findSignalements(TabQueryParameters $tabQueryParameters): array
    {
        $conn = $this->entityManager->getConnection();

        $sql = <<<SQL
            SELECT
                si.uuid,
                si.id,
                si.reference,
                si.nom_occupant,
                si.prenom_occupant,
                CONCAT_WS(', ', si.adresse_occupant, CONCAT(si.cp_occupant, ' ', si.ville_occupant)) AS fullAddress,
                relances_usager.nb_relances,
                relances_usager.first_relance_at,
                last_usager_suivi.shared_usager_at AS last_suivi_shared_usager_at,
                last_usager_suivi.type AS last_suivi_type
        SQL;
        $territoriesIds = $tabQueryParameters->territoireId ? [$tabQueryParameters->territoireId] : null;
        $sql .= $this->getBaseSql($territoriesIds);

        if ('ASC' === $tabQueryParameters->orderBy && 'nbRelanceFeedbackUsager' === $tabQueryParameters->sortBy) {
            $sql .= ' ORDER BY relances_usager.nb_relances ASC, relances_usager.first_relance_at DESC LIMIT 5';
        } else {
            $sql .= ' ORDER BY relances_usager.nb_relances DESC, relances_usager.first_relance_at DESC LIMIT 5';
        }

        if (null === $territoriesIds) {
            $rows = $conn->executeQuery($sql)->fetchAllAssociative();
        } else {
            $rows = $conn->executeQuery($sql,
                ['territories_ids' => $territoriesIds],
                ['territories_ids' => ArrayParameterType::INTEGER]
            )->fetchAllAssociative();
        }

        return array_map(/**
         * @throws \DateMalformedStringException
         */ static function (array $row): TabDossier {
            return new TabDossier(
                uuid: $row['uuid'],
                nomOccupant: $row['nom_occupant'],
                prenomOccupant: $row['prenom_occupant'],
                reference: $row['reference'],
                adresse: $row['fullAddress'],
                nbRelanceDossier: (int) $row['nb_relances'],
                premiereRelanceDossierAt: new \DateTimeImmutable($row['first_relance_at']),
                dernierSuiviPublicAt: new \DateTimeImmutable($row['last_suivi_shared_usager_at']),
                dernierTypeSuivi: (string) $row['last_suivi_type'],
            );
        }, $rows);
    }

    public function countSignalements(TabQueryParameters $tabQueryParameters): int
    {
        $conn = $this->entityManager->getConnection();
        $territoriesIds = $tabQueryParameters->territoireId ? [$tabQueryParameters->territoireId] : null;
        $sql = 'SELECT COUNT(*) FROM (SELECT relances_usager.signalement_id '.$this->getBaseSql($territoriesIds).') AS signalements_count';

        if (null === $territoriesIds) {
            return (int) $conn->executeQuery($sql)->fetchOne();
        }

        return (int) $conn->executeQuery($sql,
            ['territories_ids' => $territoriesIds],
            ['territories_ids' => ArrayParameterType::INTEGER]
        )->fetchOne();
    }

    /**
     * @return int[]
     */
    public function getSignalementsId(User $user): array
    {
        $conn = $this->entityManager->getConnection();
        $territoriesIds = [];
        if (!$user->isSuperAdmin()) {
            foreach ($user->getPartnersTerritories() as $territory) {
                $territoriesIds[] = $territory->getId();
            }
        }
        $territoriesIds = empty($territoriesIds) ? null : $territoriesIds;
        $sql = 'SELECT si.id '.$this->getBaseSql($territoriesIds);

        if (null === $territoriesIds) {
            return array_map('intval', $conn->executeQuery($sql)->fetchFirstColumn());
        }

        return array_map('intval', $conn->executeQuery($sql,
            ['territories_ids' => $territoriesIds],
            ['territories_ids' => ArrayParameterType::INTEGER]
        )->fetchFirstColumn());
    }
}
