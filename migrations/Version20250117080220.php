<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250117080220 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add createdBy on signalement';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE signalement ADD created_by_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE signalement ADD CONSTRAINT FK_F4B55114B03A8386 FOREIGN KEY (created_by_id) REFERENCES user (id)');
        $this->addSql('CREATE INDEX IDX_F4B55114B03A8386 ON signalement (created_by_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE signalement DROP FOREIGN KEY FK_F4B55114B03A8386');
        $this->addSql('DROP INDEX IDX_F4B55114B03A8386 ON signalement');
        $this->addSql('ALTER TABLE signalement DROP created_by_id');
    }
}
