<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250319142447 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'separate nom and denomination from proprio';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE signalement ADD denomination_proprio VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE signalement DROP denomination_proprio');
    }
}
