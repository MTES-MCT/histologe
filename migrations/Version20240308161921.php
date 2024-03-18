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
        $this->addSql('CREATE TABLE bailleur (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, is_social TINYINT(1) NOT NULL, active TINYINT(1) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE bailleur_territory (bailleur_id INT NOT NULL, territory_id INT NOT NULL, INDEX IDX_7A87051F57B5D0A2 (bailleur_id), INDEX IDX_7A87051F73F74AD4 (territory_id), PRIMARY KEY(bailleur_id, territory_id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE bailleur_territory ADD CONSTRAINT FK_7A87051F57B5D0A2 FOREIGN KEY (bailleur_id) REFERENCES bailleur (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE bailleur_territory ADD CONSTRAINT FK_7A87051F73F74AD4 FOREIGN KEY (territory_id) REFERENCES territory (id) ON DELETE CASCADE');

        $this->addSql('ALTER TABLE signalement ADD bailleur_id INT DEFAULT NULL, CHANGE is_usager_abandon_procedure is_usager_abandon_procedure TINYINT(1) DEFAULT NULL, CHANGE date_naissance_occupant date_naissance_occupant DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE score_logement score_logement DOUBLE PRECISION NOT NULL, CHANGE score_batiment score_batiment DOUBLE PRECISION NOT NULL');
        $this->addSql('ALTER TABLE signalement ADD CONSTRAINT FK_F4B5511457B5D0A2 FOREIGN KEY (bailleur_id) REFERENCES bailleur (id)');
        $this->addSql('CREATE INDEX IDX_F4B5511457B5D0A2 ON signalement (bailleur_id)');

        $this->addSql('UPDATE territory SET name = "Seine-Saint-Denis" WHERE zip = "93"');
        $this->addSql('UPDATE territory SET name = "Côtes-d\'Armor" WHERE zip = "22"');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE bailleur_territory DROP FOREIGN KEY FK_7A87051F57B5D0A2');
        $this->addSql('ALTER TABLE bailleur_territory DROP FOREIGN KEY FK_7A87051F73F74AD4');
        $this->addSql('DROP TABLE bailleur');
        $this->addSql('DROP TABLE bailleur_territory');

        $this->addSql('ALTER TABLE signalement DROP FOREIGN KEY FK_F4B5511457B5D0A2');
        $this->addSql('DROP INDEX IDX_F4B5511457B5D0A2 ON signalement');
        $this->addSql('ALTER TABLE signalement DROP bailleur_id');
    }
}
