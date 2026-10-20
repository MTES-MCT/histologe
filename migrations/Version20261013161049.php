<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261013161049 extends AbstractMigration
{
    private const string ADMIN_EMAIL = 'admin@signal-logement.beta.gouv.fr';

    public function getDescription(): string
    {
        return 'Renseigne l\'utilisateur système comme auteur des suivis de clôture créés sans auteur par le cron app:remind-injonction-signalement';
    }

    public function up(Schema $schema): void
    {
        $adminUserId = $this->connection->fetchOne('SELECT id FROM user WHERE email = ?', [self::ADMIN_EMAIL]);

        $this->addSql(
            <<<'SQL'
                UPDATE suivi
                SET created_by_id = ?
                WHERE category = 'SIGNALEMENT_IS_CLOSED'
                  AND created_by_id IS NULL
                SQL,
            [$adminUserId]
        );
    }

    public function down(Schema $schema): void
    {
        $this->throwIrreversibleMigrationException();
    }
}
