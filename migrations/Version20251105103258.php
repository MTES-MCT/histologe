<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251105103258 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Set SignalementDraft status to EN_SIGNALEMENT for drafts linked to Signalement';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("
            UPDATE signalement_draft d
            INNER JOIN signalement s ON d.id = s.created_from_id
            SET d.status = 'EN_SIGNALEMENT'
            WHERE d.status != 'EN_SIGNALEMENT';
        ");
    }

    public function down(Schema $schema): void
    {
    }
}
