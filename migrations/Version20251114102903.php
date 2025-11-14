<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251114102903 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajout de bailleur_prevenu_at et migration des anciennes dates info_procedure_bail_date (m/Y) vers datetime (1er jour du mois).';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE signalement_draft ADD bailleur_prevenu_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql("
            UPDATE signalement_draft
            SET bailleur_prevenu_at = STR_TO_DATE(
                CONCAT(
                    '01/',
                    JSON_UNQUOTE(JSON_EXTRACT(payload, '$.info_procedure_bail_date'))
                ),
                '%d/%m/%Y'
            )
            WHERE JSON_EXTRACT(payload, '$.info_procedure_bail_date') IS NOT NULL
              AND JSON_UNQUOTE(JSON_EXTRACT(payload, '$.info_procedure_bail_date')) <> ''
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE signalement_draft DROP bailleur_prevenu_at');
    }
}
