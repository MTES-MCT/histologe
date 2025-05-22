<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use App\Entity\Enum\SignalementDraftStatus;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250520122018 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'add pending_draft_reminded_at to signalement_draft';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE signalement_draft ADD pending_draft_reminded_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');

        $this->addSql('UPDATE `signalement_draft` s SET `pending_draft_reminded_at` = \'1970-01-01\' WHERE s.status = \''.SignalementDraftStatus::EN_COURS->value.'\' AND s.current_step = \'info_procedure_bail\'');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE signalement_draft DROP pending_draft_reminded_at');
    }
}
