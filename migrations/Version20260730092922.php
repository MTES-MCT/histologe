<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260730092922 extends AbstractMigration
{
    private const string ADMIN_EMAIL = 'admin@signal-logement.beta.gouv.fr';

    private const array CLOTURE_SUIVI_CATEGORIES = [
        'INJONCTION_BAILLEUR_CLOTURE_PAR_USAGER',
        'INJONCTION_BAILLEUR_CLOTURE_PAR_ADMIN',
        'SIGNALEMENT_IS_CLOSED',
        'INJONCTION_BAILLEUR_CLOTURE_SANS_ACTIVITE',
    ];

    public function getDescription(): string
    {
        return 'Backfill motif_cloture / closed_at / com_cloture / closed_by_id des signalements en injonction déjà clôturés, et clôture des affectations restées ouvertes sur ces dossiers';
    }

    public function up(Schema $schema): void
    {
        $categoriesPlaceholders = implode(', ', array_fill(0, count(self::CLOTURE_SUIVI_CATEGORIES), '?'));
        $adminUserId = $this->connection->fetchOne('SELECT id FROM user WHERE email = ?', [self::ADMIN_EMAIL]);

        // 1. motif_cloture manquant : l'ancien code ne renseignait que motif_cloture_usager
        // lors d'une clôture d'injonction par l'usager ou par un administrateur
        $this->addSql(
            <<<'SQL'
                UPDATE signalement
                SET motif_cloture = CASE motif_cloture_usager
                    WHEN 'ACCORD_PROPRIETAIRE' THEN 'AUTRE'
                    WHEN 'RELOGEMENT_OCCUPANT' THEN 'RELOGEMENT_OCCUPANT'
                    WHEN 'TRAVAUX_FAITS_OU_EN_COURS' THEN 'TRAVAUX_FAITS_OU_EN_COURS'
                    WHEN 'AUTRE' THEN 'AUTRE'
                    ELSE motif_cloture
                END
                WHERE statut = 'INJONCTION_CLOSED'
                  AND motif_cloture IS NULL
                  AND motif_cloture_usager IS NOT NULL
                SQL
        );

        // 2. closed_at manquant : on reprend la date du suivi de clôture s'il existe, sinon la dernière modification du dossier
        $this->addSql(
            <<<SQL
                UPDATE signalement s
                SET s.closed_at = COALESCE(
                    (SELECT MAX(su.created_at) FROM suivi su WHERE su.signalement_id = s.id AND su.category IN ({$categoriesPlaceholders})),
                    s.modified_at,
                    s.created_at
                )
                WHERE s.statut = 'INJONCTION_CLOSED'
                  AND s.closed_at IS NULL
                SQL,
            self::CLOTURE_SUIVI_CATEGORIES
        );

        // 3. com_cloture manquant : on reprend la description du suivi de clôture, sinon un texte générique
        $this->addSql(
            <<<SQL
                UPDATE signalement s
                SET s.com_cloture = COALESCE(
                    (SELECT su.description FROM suivi su WHERE su.signalement_id = s.id AND su.category IN ({$categoriesPlaceholders}) ORDER BY su.created_at DESC LIMIT 1),
                    'Clôture historique de la démarche accélérée.'
                )
                WHERE s.statut = 'INJONCTION_CLOSED'
                  AND s.com_cloture IS NULL
                SQL,
            self::CLOTURE_SUIVI_CATEGORIES
        );

        // 4. closed_by_id manquant : on reprend l'auteur du suivi de clôture s'il existe, sinon l'utilisateur système
        $this->addSql(
            <<<SQL
                UPDATE signalement s
                SET s.closed_by_id = COALESCE(
                    (SELECT su.created_by_id FROM suivi su WHERE su.signalement_id = s.id AND su.category IN ({$categoriesPlaceholders}) ORDER BY su.created_at DESC LIMIT 1),
                    ?
                )
                WHERE s.statut = 'INJONCTION_CLOSED'
                  AND s.closed_by_id IS NULL
                SQL,
            [...self::CLOTURE_SUIVI_CATEGORIES, $adminUserId]
        );

        // 5. Affectations restées ouvertes sur un dossier en injonction déjà clôturé : on les clôture avec le même motif
        $this->addSql(
            <<<'SQL'
                UPDATE affectation a
                INNER JOIN signalement s ON s.id = a.signalement_id
                SET a.statut = 'FERME',
                    a.motif_cloture = COALESCE(s.motif_cloture, 'AUTRE'),
                    a.answered_at = COALESCE(s.closed_at, NOW()),
                    a.answered_by_id = ?
                WHERE s.statut = 'INJONCTION_CLOSED'
                  AND a.statut IN ('NOUVEAU', 'EN_COURS')
                SQL,
            [$adminUserId]
        );
    }

    public function down(Schema $schema): void
    {
        $this->throwIrreversibleMigrationException();
    }
}
