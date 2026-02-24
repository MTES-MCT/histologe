<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260223163240 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add new field "autre_situation_vulnerabilite" to signalement table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE signalement ADD autre_situation_vulnerabilite LONGTEXT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE signalement DROP autre_situation_vulnerabilite');
    }
}
