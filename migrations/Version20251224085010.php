<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251224085010 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create club_event table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE club_event (id INT AUTO_INCREMENT NOT NULL, date_event DATETIME NOT NULL, name VARCHAR(50) NOT NULL, url VARCHAR(255) NOT NULL, user_roles JSON NOT NULL, partner_types JSON NOT NULL, partner_competences JSON NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE club_event');
    }
}
