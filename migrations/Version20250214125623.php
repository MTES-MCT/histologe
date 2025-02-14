<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250214125623 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add index for cp_occupant column in signalement table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE INDEX idx_signalement_cp_occupant ON signalement (cp_occupant)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX idx_signalement_cp_occupant ON signalement');
    }
}
