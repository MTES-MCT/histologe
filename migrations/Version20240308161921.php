<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20240308161921 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add bailleur table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE bailleur (id INT AUTO_INCREMENT NOT NULL, territory_id INT NOT NULL, name VARCHAR(255) NOT NULL, is_social TINYINT(1) NOT NULL, active TINYINT(1) NOT NULL, INDEX IDX_7ABB27F373F74AD4 (territory_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE bailleur ADD CONSTRAINT FK_7ABB27F373F74AD4 FOREIGN KEY (territory_id) REFERENCES territory (id)');
        $this->addSql('UPDATE territory SET name = "Seine-Saint-Denis" WHERE zip = "93"');
        $this->addSql('UPDATE territory SET name = "Côtes-d\'Armor" WHERE zip = "22"');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE bailleur DROP FOREIGN KEY FK_7ABB27F373F74AD4');
        $this->addSql('DROP TABLE bailleur');
    }
}
