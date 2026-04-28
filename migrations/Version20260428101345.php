<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260428101345 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajout d\'une relation entre signalement et suivi pour stocker le dernier suivi d\'un signalement';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE signalement ADD last_suivi_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE signalement ADD CONSTRAINT FK_F4B5511478B1C5E FOREIGN KEY (last_suivi_id) REFERENCES suivi (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_F4B5511478B1C5E ON signalement (last_suivi_id)');
        // Populate last_suivi_id, last_suivi_at and last_suivi_is_public from the most recent non-deleted suivi per signalement.
        $this->addSql('
            UPDATE signalement s
            INNER JOIN suivi sv ON sv.id = (
                SELECT id FROM suivi
                WHERE signalement_id = s.id AND deleted_at IS NULL
                ORDER BY created_at DESC
                LIMIT 1
            )
            SET
                s.last_suivi_id = sv.id,
                s.last_suivi_at = sv.created_at,
                s.last_suivi_is_public = sv.is_public
        ');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE signalement DROP FOREIGN KEY FK_F4B5511478B1C5E');
        $this->addSql('DROP INDEX IDX_F4B5511478B1C5E ON signalement');
        $this->addSql('ALTER TABLE signalement DROP last_suivi_id');
    }
}
