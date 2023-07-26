<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20230720102750 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add table signalement draft';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE signalement_draft (id INT AUTO_INCREMENT NOT NULL, uuid VARCHAR(255) NOT NULL, profile_declarant VARCHAR(255) DEFAULT NULL, email_declarant VARCHAR(255) NOT NULL, address_complete VARCHAR(255) NOT NULL, payload JSON NOT NULL, current_step VARCHAR(128) NOT NULL, status VARCHAR(255) DEFAULT NULL, created_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE signalement ADD created_from_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE signalement ADD CONSTRAINT FK_F4B551143EA4CB4D FOREIGN KEY (created_from_id) REFERENCES signalement_draft (id)');
        $this->addSql('CREATE INDEX IDX_F4B551143EA4CB4D ON signalement (created_from_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE signalement DROP FOREIGN KEY FK_F4B551143EA4CB4D');
        $this->addSql('DROP INDEX IDX_F4B551143EA4CB4D ON signalement');
        $this->addSql('ALTER TABLE signalement DROP created_from_id');
        $this->addSql('DROP TABLE signalement_draft');
    }
}
