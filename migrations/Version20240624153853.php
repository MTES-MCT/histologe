<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20240624153853 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'add table HistoryEntry';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE history_entry (id INT AUTO_INCREMENT NOT NULL, user_id INT DEFAULT NULL, event VARCHAR(255) NOT NULL, entity_id INT DEFAULT NULL, entity_name VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_8F3F68C5A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE history_entry ADD CONSTRAINT FK_8F3F68C5A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE history_entry DROP FOREIGN KEY FK_8F3F68C5A76ED395');
        $this->addSql('DROP TABLE history_entry');
    }
}
