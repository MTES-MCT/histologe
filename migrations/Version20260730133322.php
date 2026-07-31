<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260730133322 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create in_app_communication and in_app_communication_user tables';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE in_app_communication (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(255) DEFAULT NULL, description VARCHAR(255) DEFAULT NULL, url VARCHAR(255) DEFAULT NULL, url_title VARCHAR(255) DEFAULT NULL, communication_type VARCHAR(255) NOT NULL, user_roles JSON NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE in_app_communication_user (id INT AUTO_INCREMENT NOT NULL, seen_at DATETIME DEFAULT NULL, closed_at DATETIME DEFAULT NULL, user_id INT NOT NULL, in_app_communication_id INT NOT NULL, INDEX IDX_D9B222C3A76ED395 (user_id), INDEX IDX_D9B222C313F0E189 (in_app_communication_id), UNIQUE INDEX user_in_app_communication_unique (user_id, in_app_communication_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE in_app_communication_user ADD CONSTRAINT FK_D9B222C3A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE in_app_communication_user ADD CONSTRAINT FK_D9B222C313F0E189 FOREIGN KEY (in_app_communication_id) REFERENCES in_app_communication (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE in_app_communication_user DROP FOREIGN KEY FK_D9B222C3A76ED395');
        $this->addSql('ALTER TABLE in_app_communication_user DROP FOREIGN KEY FK_D9B222C313F0E189');
        $this->addSql('DROP TABLE in_app_communication');
        $this->addSql('DROP TABLE in_app_communication_user');
    }
}
