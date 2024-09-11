<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20240911122535 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Remove mode_contact_proprio column';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE signalement DROP mode_contact_proprio');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE signalement ADD mode_contact_proprio JSON DEFAULT NULL');
    }
}
