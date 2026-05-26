<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260512153353 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'migrate signalement.structureReferentSocial to json field signalement.situationFoyer.travailleur_social_accompagnement_nom_structure';
    }

    public function up(Schema $schema): void
    {
        // Copy data from structureReferentSocial in travailleur_social_accompagnement_nom_structure in json field situationFoyer
        $this->addSql('UPDATE signalement s SET s.situation_foyer = JSON_SET(COALESCE(s.situation_foyer, \'{}\'), \'$.travailleur_social_accompagnement_nom_structure\', s.structure_referent_social) WHERE s.structure_referent_social IS NOT NULL');
        // Copy data from nomReferentSocial in travailleur_social_accompagnement_nom_referent in json field situationFoyer
        $this->addSql('UPDATE signalement s SET s.situation_foyer = JSON_SET(COALESCE(s.situation_foyer, \'{}\'), \'$.travailleur_social_accompagnement_nom_referent\', s.nom_referent_social) WHERE s.nom_referent_social IS NOT NULL');
        // Remove columns structureReferentSocial and nomReferentSocial
        $this->addSql('ALTER TABLE signalement DROP structure_referent_social');
        $this->addSql('ALTER TABLE signalement DROP nom_referent_social');
    }

    public function down(Schema $schema): void
    {
        // add columns structureReferentSocial and nomReferentSocial
        $this->addSql('ALTER TABLE signalement ADD structure_referent_social VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE signalement ADD nom_referent_social VARCHAR(255) DEFAULT NULL');
        // Copy data from travailleur_social_accompagnement_nom_structure in json field situationFoyer to structureReferentSocial
        $this->addSql('UPDATE signalement s SET s.structure_referent_social = JSON_UNQUOTE(JSON_EXTRACT(s.situation_foyer, \'$.travailleur_social_accompagnement_nom_structure\')) WHERE JSON_EXTRACT(s.situation_foyer, \'$.travailleur_social_accompagnement_nom_structure\') IS NOT NULL');
        // Copy data from travailleur_social_accompagnement_nom_referent in json field situationFoyer to nomReferentSocial
        $this->addSql('UPDATE signalement s SET s.nom_referent_social = JSON_UNQUOTE(JSON_EXTRACT(s.situation_foyer, \'$.travailleur_social_accompagnement_nom_referent\')) WHERE JSON_EXTRACT(s.situation_foyer, \'$.travailleur_social_accompagnement_nom_referent\') IS NOT NULL');
    }
}
