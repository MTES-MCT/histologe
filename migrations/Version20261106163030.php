<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261106163030 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Make fields not nullable';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE club_event CHANGE date_event date_event DATETIME NOT NULL');
        $this->addSql('ALTER TABLE file CHANGE document_type document_type VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE signalement CHANGE profile_declarant profile_declarant VARCHAR(50) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE club_event CHANGE date_event date_event DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE file CHANGE document_type document_type VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE signalement CHANGE profile_declarant profile_declarant VARCHAR(50) DEFAULT NULL');
    }
}
