<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260929100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add accompagnement_travailleur_social, demande_logement_social, zone_to_include and zone_to_exclude columns to auto_affectation_rule table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE auto_affectation_rule ADD accompagnement_travailleur_social VARCHAR(32) DEFAULT \'all\' NOT NULL COMMENT \'Value possible all, oui, non or nsp\', ADD demande_logement_social VARCHAR(32) DEFAULT \'all\' NOT NULL COMMENT \'Value possible all, oui, non or nsp\', ADD zone_to_include JSON DEFAULT NULL COMMENT \'Value possible null or an array of zone ids\', ADD zone_to_exclude JSON DEFAULT NULL COMMENT \'Value possible null or an array of zone ids\'');
        $this->addSql('ALTER TABLE auto_affectation_rule ALTER accompagnement_travailleur_social DROP DEFAULT, ALTER demande_logement_social DROP DEFAULT');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE auto_affectation_rule DROP accompagnement_travailleur_social, DROP demande_logement_social, DROP zone_to_include, DROP zone_to_exclude');
    }
}
