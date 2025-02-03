<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250203165635 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'change signalement statut to enum';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE signalement CHANGE statut statut VARCHAR(255) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE signalement CHANGE statut statut INT NOT NULL');
    }
}
