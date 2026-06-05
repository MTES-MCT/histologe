<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260605142413 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Remove unused columns in signalement table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE signalement DROP is_refus_intervention, DROP raison_refus_intervention, DROP code_procedure, DROP is_consentement_tiers, DROP type_energie_logement, DROP origine_signalement, DROP situation_occupant, DROP situation_pro_occupant, DROP is_logement_collectif, DROP is_diag_socio_technique, DROP is_fond_solidarite_logement, DROP is_risque_sur_occupation, DROP nb_chambres_logement, CHANGE uuid uuid VARCHAR(50) NOT NULL, CHANGE profile_declarant profile_declarant VARCHAR(50) DEFAULT NULL, CHANGE nb_adultes nb_adultes VARCHAR(3) DEFAULT NULL, CHANGE nb_enfants_m6 nb_enfants_m6 VARCHAR(3) DEFAULT NULL, CHANGE nb_enfants_p6 nb_enfants_p6 VARCHAR(3) DEFAULT NULL, CHANGE type_proprio type_proprio VARCHAR(50) DEFAULT NULL, CHANGE ville_proprio ville_proprio VARCHAR(50) DEFAULT NULL, CHANGE statut statut VARCHAR(50) NOT NULL, CHANGE reference reference VARCHAR(20) DEFAULT NULL, CHANGE insee_occupant insee_occupant VARCHAR(10) DEFAULT NULL, CHANGE code_suivi code_suivi VARCHAR(100) DEFAULT NULL, CHANGE lien_declarant_occupant lien_declarant_occupant VARCHAR(50) DEFAULT NULL, CHANGE annee_construction annee_construction VARCHAR(20) DEFAULT NULL, CHANGE motif_cloture motif_cloture VARCHAR(50) DEFAULT NULL, CHANGE motif_refus motif_refus VARCHAR(50) DEFAULT NULL, CHANGE rnb_id_occupant rnb_id_occupant VARCHAR(20) DEFAULT NULL, CHANGE numero_invariant_rial numero_invariant_rial VARCHAR(20) DEFAULT NULL, CHANGE login_bailleur login_bailleur VARCHAR(50) DEFAULT NULL, CHANGE profile_occupant profile_occupant VARCHAR(50) DEFAULT NULL, CHANGE creation_source creation_source VARCHAR(50) DEFAULT NULL, CHANGE motif_cloture_usager motif_cloture_usager VARCHAR(50) DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_F4B55114D17F50A6 ON signalement (uuid)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX UNIQ_F4B55114D17F50A6 ON signalement');
        $this->addSql('ALTER TABLE signalement ADD is_refus_intervention TINYINT(1) DEFAULT NULL, ADD raison_refus_intervention LONGTEXT DEFAULT NULL, ADD code_procedure VARCHAR(255) DEFAULT NULL, ADD is_consentement_tiers TINYINT(1) DEFAULT NULL, ADD type_energie_logement VARCHAR(255) DEFAULT NULL, ADD origine_signalement VARCHAR(255) DEFAULT NULL, ADD situation_occupant VARCHAR(255) DEFAULT NULL, ADD situation_pro_occupant VARCHAR(255) DEFAULT NULL, ADD is_logement_collectif TINYINT(1) DEFAULT NULL, ADD is_diag_socio_technique TINYINT(1) DEFAULT NULL, ADD is_fond_solidarite_logement TINYINT(1) DEFAULT NULL, ADD is_risque_sur_occupation TINYINT(1) DEFAULT NULL, ADD nb_chambres_logement INT DEFAULT NULL, CHANGE uuid uuid VARCHAR(255) NOT NULL, CHANGE profile_declarant profile_declarant VARCHAR(255) DEFAULT NULL, CHANGE profile_occupant profile_occupant VARCHAR(255) DEFAULT NULL COMMENT \'Value possible enum ProfileOccupant\', CHANGE nb_adultes nb_adultes VARCHAR(255) DEFAULT NULL, CHANGE nb_enfants_m6 nb_enfants_m6 VARCHAR(255) DEFAULT NULL, CHANGE nb_enfants_p6 nb_enfants_p6 VARCHAR(255) DEFAULT NULL, CHANGE type_proprio type_proprio VARCHAR(255) DEFAULT NULL, CHANGE ville_proprio ville_proprio VARCHAR(255) DEFAULT NULL, CHANGE statut statut VARCHAR(255) NOT NULL, CHANGE reference reference VARCHAR(100) NOT NULL, CHANGE insee_occupant insee_occupant VARCHAR(255) DEFAULT NULL, CHANGE code_suivi code_suivi VARCHAR(255) DEFAULT NULL, CHANGE lien_declarant_occupant lien_declarant_occupant VARCHAR(255) DEFAULT NULL, CHANGE annee_construction annee_construction VARCHAR(255) DEFAULT NULL, CHANGE numero_invariant_rial numero_invariant_rial VARCHAR(255) DEFAULT NULL, CHANGE motif_cloture motif_cloture VARCHAR(255) DEFAULT NULL, CHANGE motif_cloture_usager motif_cloture_usager VARCHAR(255) DEFAULT NULL, CHANGE motif_refus motif_refus VARCHAR(255) DEFAULT NULL, CHANGE rnb_id_occupant rnb_id_occupant VARCHAR(255) DEFAULT NULL, CHANGE creation_source creation_source VARCHAR(255) DEFAULT NULL, CHANGE login_bailleur login_bailleur VARCHAR(255) DEFAULT NULL');
    }
}
