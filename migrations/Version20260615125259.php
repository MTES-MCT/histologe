<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260615125259 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajout de l\'entité Address et de l\'entité Arrete, avec une relation entre les deux';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE address (id INT AUTO_INCREMENT NOT NULL, housenumber VARCHAR(10) DEFAULT NULL, street VARCHAR(100) NOT NULL, city VARCHAR(100) NOT NULL, post_code VARCHAR(5) NOT NULL, city_code VARCHAR(5) NOT NULL, ban_id VARCHAR(100) DEFAULT NULL, point POINT DEFAULT NULL COMMENT \'(DC2Type:point)\', PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE arrete (id INT AUTO_INCREMENT NOT NULL, address_id INT NOT NULL, date_arrete DATE NOT NULL COMMENT \'(DC2Type:date_immutable)\', type_arrete VARCHAR(255) NOT NULL, syndic VARCHAR(255) DEFAULT NULL, main_levee TINYINT(1) NOT NULL, date_main_levee DATE DEFAULT NULL COMMENT \'(DC2Type:date_immutable)\', INDEX IDX_8D9860AF5B7AF75 (address_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE arrete ADD CONSTRAINT FK_8D9860AF5B7AF75 FOREIGN KEY (address_id) REFERENCES address (id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE arrete DROP FOREIGN KEY FK_8D9860AF5B7AF75');
        $this->addSql('DROP TABLE address');
        $this->addSql('DROP TABLE arrete');
    }
}
