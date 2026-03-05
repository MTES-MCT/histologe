<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260303152631 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Migration pour corriger la colonne debut_desordres de la table signalement';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("
            UPDATE signalement s
            INNER JOIN signalement_draft sd ON s.created_from_id = sd.id
            SET s.debut_desordres = UPPER(JSON_UNQUOTE(JSON_EXTRACT(sd.payload, '$.zone_concernee_debut_desordres')))
            WHERE s.debut_desordres IS NULL
              AND s.created_from_id IS NOT NULL
              AND JSON_EXTRACT(sd.payload, '$.zone_concernee_debut_desordres') IS NOT NULL
              AND JSON_EXTRACT(sd.payload, '$.zone_concernee_debut_desordres') != 'null'
        ");
    }

    public function down(Schema $schema): void
    {
    }
}
