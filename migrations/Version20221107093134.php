<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20221107093134 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE signalement ADD closed_by_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE signalement ADD CONSTRAINT FK_F4B55114E1FA7797 FOREIGN KEY (closed_by_id) REFERENCES user (id)');
        $this->addSql('CREATE INDEX IDX_F4B55114E1FA7797 ON signalement (closed_by_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE signalement DROP FOREIGN KEY FK_F4B55114E1FA7797');
        $this->addSql('DROP INDEX IDX_F4B55114E1FA7797 ON signalement');
        $this->addSql('ALTER TABLE signalement DROP closed_by_id');
    }
}
