<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250611085717 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create initial database schema.';
    }

    public function up(Schema $schema): void
    {
        $isEmpty = $schema->hasTable('signalement');
        $this->skipIf($isEmpty, 'This migration should only be run on empty database.');

        $this->addSql(<<<'SQL'
            CREATE TABLE affectation (id INT AUTO_INCREMENT NOT NULL, signalement_id INT NOT NULL, partner_id INT NOT NULL, answered_by_id INT DEFAULT NULL, affected_by_id INT DEFAULT NULL, territory_id INT DEFAULT NULL, uuid VARCHAR(255) NOT NULL, answered_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)', created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', statut INT NOT NULL, is_synchronized TINYINT(1) NOT NULL, motif_refus VARCHAR(255) DEFAULT NULL, motif_cloture VARCHAR(255) DEFAULT NULL, UNIQUE INDEX UNIQ_F4DD61D3D17F50A6 (uuid), INDEX IDX_F4DD61D365C5E57E (signalement_id), INDEX IDX_F4DD61D39393F8FE (partner_id), INDEX IDX_F4DD61D32FC55A77 (answered_by_id), INDEX IDX_F4DD61D369E36731 (affected_by_id), INDEX IDX_F4DD61D373F74AD4 (territory_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE api_user_token (id INT AUTO_INCREMENT NOT NULL, owned_by_id INT NOT NULL, token VARCHAR(64) NOT NULL, expires_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', UNIQUE INDEX UNIQ_7A5F26725F37A13B (token), INDEX IDX_7A5F26725E70BCD7 (owned_by_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE auto_affectation_rule (id INT AUTO_INCREMENT NOT NULL, territory_id INT DEFAULT NULL, status VARCHAR(255) NOT NULL COMMENT 'Value possible ACTIVE or ARCHIVED', partner_type VARCHAR(255) NOT NULL COMMENT 'Value possible enum PartnerType', profile_declarant VARCHAR(255) NOT NULL COMMENT 'Value possible enum ProfileDeclarant or all, tiers or occupant', insee_to_include VARCHAR(255) NOT NULL COMMENT 'Value possible empty or an array of code insee', insee_to_exclude JSON DEFAULT NULL COMMENT 'Value possible null or an array of code insee', partner_to_exclude JSON DEFAULT NULL COMMENT 'Value possible null or an array of partner ids', parc VARCHAR(32) NOT NULL COMMENT 'Value possible all, non_renseigne, prive or public', allocataire VARCHAR(32) NOT NULL COMMENT 'Value possible all, non, oui, caf, msa or nsp', procedures_suspectees LONGTEXT DEFAULT NULL COMMENT '(DC2Type:simple_array)', INDEX IDX_1A302A1C73F74AD4 (territory_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE bailleur (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, raison_sociale VARCHAR(255) DEFAULT NULL, siret VARCHAR(20) DEFAULT NULL, UNIQUE INDEX UNIQ_7ABB27F35E237E06 (name), UNIQUE INDEX UNIQ_7ABB27F3E2B45978 (raison_sociale), UNIQUE INDEX UNIQ_7ABB27F326E94372 (siret), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE bailleur_territory (id INT AUTO_INCREMENT NOT NULL, bailleur_id INT NOT NULL, territory_id INT NOT NULL, INDEX IDX_7A87051F57B5D0A2 (bailleur_id), INDEX IDX_7A87051F73F74AD4 (territory_id), UNIQUE INDEX UNIQ_7A87051F57B5D0A273F74AD4 (bailleur_id, territory_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE commune (id INT AUTO_INCREMENT NOT NULL, territory_id INT DEFAULT NULL, epci_id INT DEFAULT NULL, nom VARCHAR(255) NOT NULL, code_postal VARCHAR(10) NOT NULL, code_insee VARCHAR(10) NOT NULL, is_zone_permis_louer TINYINT(1) NOT NULL, INDEX IDX_E2E2D1EE73F74AD4 (territory_id), INDEX IDX_E2E2D1EE4E7C18CB (epci_id), UNIQUE INDEX code_postal_code_insee_unique (code_postal, code_insee), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE critere (id INT AUTO_INCREMENT NOT NULL, situation_id INT NOT NULL, label VARCHAR(255) NOT NULL, description LONGTEXT NOT NULL, created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', modified_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)', is_archive TINYINT(1) NOT NULL, is_danger TINYINT(1) NOT NULL, coef INT NOT NULL, new_coef INT NOT NULL, type INT NOT NULL, INDEX IDX_7F6A80533408E8AF (situation_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE criticite (id INT AUTO_INCREMENT NOT NULL, critere_id INT NOT NULL, label LONGTEXT NOT NULL, created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', modified_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)', score INT NOT NULL, new_score DOUBLE PRECISION NOT NULL, is_danger TINYINT(1) NOT NULL, is_archive TINYINT(1) NOT NULL, is_default TINYINT(1) NOT NULL, qualification JSON DEFAULT NULL, INDEX IDX_6F33ED989E5F45AB (critere_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE desordre_categorie (id INT AUTO_INCREMENT NOT NULL, label VARCHAR(255) NOT NULL, created_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)', updated_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)', PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE desordre_critere (id INT AUTO_INCREMENT NOT NULL, desordre_categorie_id INT NOT NULL, slug_categorie VARCHAR(255) NOT NULL, label_categorie VARCHAR(255) NOT NULL, zone_categorie VARCHAR(255) NOT NULL, slug_critere VARCHAR(255) NOT NULL, label_critere VARCHAR(255) NOT NULL, created_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)', updated_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)', INDEX IDX_6DCB3D10ECF01477 (desordre_categorie_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE desordre_precision (id INT AUTO_INCREMENT NOT NULL, desordre_critere_id INT NOT NULL, coef DOUBLE PRECISION NOT NULL, is_danger TINYINT(1) DEFAULT NULL, is_suroccupation TINYINT(1) DEFAULT NULL, is_insalubrite TINYINT(1) DEFAULT NULL, label VARCHAR(255) DEFAULT NULL, qualification JSON NOT NULL, desordre_precision_slug VARCHAR(255) NOT NULL, created_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)', updated_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)', INDEX IDX_23744FA01C3935AB (desordre_critere_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE epci (id INT AUTO_INCREMENT NOT NULL, code VARCHAR(255) NOT NULL, nom VARCHAR(255) NOT NULL, UNIQUE INDEX code_unique (code), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE failed_email (id INT AUTO_INCREMENT NOT NULL, type VARCHAR(50) NOT NULL, to_email JSON NOT NULL, from_email VARCHAR(255) NOT NULL, from_fullname VARCHAR(255) NOT NULL, reply_to VARCHAR(255) NOT NULL, subject LONGTEXT DEFAULT NULL, context JSON NOT NULL, notify_usager TINYINT(1) NOT NULL, error_message LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', is_resend_successful TINYINT(1) NOT NULL, retry_count INT NOT NULL, last_attempt_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)', is_recipient_visible TINYINT(1) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE file (id INT AUTO_INCREMENT NOT NULL, uploaded_by_id INT DEFAULT NULL, signalement_id INT DEFAULT NULL, intervention_id INT DEFAULT NULL, filename VARCHAR(255) NOT NULL, title VARCHAR(255) NOT NULL, file_type VARCHAR(32) NOT NULL COMMENT 'Value possible photo or document', extension VARCHAR(255) NOT NULL, created_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)', size BIGINT DEFAULT NULL, document_type VARCHAR(255) DEFAULT NULL, desordre_slug VARCHAR(255) DEFAULT NULL, is_variants_generated TINYINT(1) NOT NULL, description TINYTEXT DEFAULT NULL, is_waiting_suivi TINYINT(1) NOT NULL, is_temp TINYINT(1) NOT NULL, synchro_data JSON DEFAULT NULL, scanned_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)', uuid VARCHAR(255) NOT NULL, is_original_deleted TINYINT(1) NOT NULL, is_suspicious TINYINT(1) DEFAULT NULL, UNIQUE INDEX UNIQ_8C9F3610D17F50A6 (uuid), INDEX IDX_8C9F3610A2B28FE8 (uploaded_by_id), INDEX IDX_8C9F361065C5E57E (signalement_id), INDEX IDX_8C9F36108EAE3863 (intervention_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE history_entry (id INT AUTO_INCREMENT NOT NULL, signalement_id INT DEFAULT NULL, user_id INT DEFAULT NULL, event VARCHAR(255) DEFAULT NULL, entity_id INT DEFAULT NULL, entity_name VARCHAR(255) NOT NULL, changes JSON DEFAULT NULL, source VARCHAR(255) DEFAULT NULL, created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', INDEX IDX_7299951765C5E57E (signalement_id), INDEX IDX_72999517A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE intervention (id INT AUTO_INCREMENT NOT NULL, signalement_id INT NOT NULL, partner_id INT DEFAULT NULL, uuid VARCHAR(255) NOT NULL, scheduled_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)', registered_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)', type VARCHAR(255) NOT NULL, status VARCHAR(255) NOT NULL, details LONGTEXT DEFAULT NULL, conclude_procedure TINYTEXT DEFAULT NULL COMMENT '(DC2Type:simple_array)', reminder_before_sent_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)', reminder_conclusion_sent_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)', occupant_present TINYINT(1) DEFAULT NULL, proprietaire_present TINYINT(1) DEFAULT NULL, done_by VARCHAR(255) DEFAULT NULL, provider_name VARCHAR(255) DEFAULT NULL COMMENT 'Provider name have created the intervention', provider_id INT DEFAULT NULL COMMENT 'Unique id used by the provider', additional_information JSON DEFAULT NULL, external_operator VARCHAR(255) DEFAULT NULL, created_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)', updated_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)', UNIQUE INDEX UNIQ_D11814ABD17F50A6 (uuid), INDEX IDX_D11814AB65C5E57E (signalement_id), INDEX IDX_D11814AB9393F8FE (partner_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE job_event (id INT AUTO_INCREMENT NOT NULL, signalement_id INT DEFAULT NULL, partner_id INT DEFAULT NULL, service VARCHAR(255) NOT NULL, partner_type VARCHAR(255) DEFAULT NULL, action VARCHAR(255) NOT NULL, message LONGTEXT DEFAULT NULL, response LONGTEXT DEFAULT NULL, status VARCHAR(255) NOT NULL, code_status INT DEFAULT NULL, created_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)', updated_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)', INDEX idx_job_event_created_at (created_at), INDEX idx_job_event_partner_id (partner_id), INDEX idx_job_event_service (service), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE notification (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, signalement_id INT DEFAULT NULL, suivi_id INT DEFAULT NULL, affectation_id INT DEFAULT NULL, is_seen TINYINT(1) NOT NULL, type VARCHAR(255) NOT NULL COMMENT 'Value possible enum NotificationType', created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', wait_mailing_summary TINYINT(1) NOT NULL, mailing_summary_sent_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)', deleted TINYINT(1) NOT NULL, seen_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)', INDEX IDX_BF5476CAA76ED395 (user_id), INDEX IDX_BF5476CA65C5E57E (signalement_id), INDEX IDX_BF5476CA7FEA59C0 (suivi_id), INDEX IDX_BF5476CA6D0ABA22 (affectation_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE partner (id INT AUTO_INCREMENT NOT NULL, territory_id INT DEFAULT NULL, bailleur_id INT DEFAULT NULL, nom VARCHAR(255) NOT NULL, is_archive TINYINT(1) NOT NULL, insee JSON NOT NULL, email VARCHAR(100) DEFAULT NULL, email_notifiable TINYINT(1) NOT NULL, esabora_url VARCHAR(255) DEFAULT NULL, esabora_token VARCHAR(255) DEFAULT NULL, type VARCHAR(255) DEFAULT NULL, competence LONGTEXT DEFAULT NULL COMMENT '(DC2Type:simple_array)', is_esabora_active TINYINT(1) DEFAULT NULL, is_idoss_active TINYINT(1) NOT NULL, idoss_url VARCHAR(255) DEFAULT NULL, idoss_token VARCHAR(255) DEFAULT NULL, idoss_token_expiration_date DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)', created_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)', updated_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)', INDEX IDX_312B3E1673F74AD4 (territory_id), INDEX IDX_312B3E1657B5D0A2 (bailleur_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE partner_zone (partner_id INT NOT NULL, zone_id INT NOT NULL, INDEX IDX_F0DFF31F9393F8FE (partner_id), INDEX IDX_F0DFF31F9F2C3FAB (zone_id), PRIMARY KEY(partner_id, zone_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE partner_excluded_zone (partner_id INT NOT NULL, zone_id INT NOT NULL, INDEX IDX_EF69B0979393F8FE (partner_id), INDEX IDX_EF69B0979F2C3FAB (zone_id), PRIMARY KEY(partner_id, zone_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE pop_notification (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, params JSON NOT NULL, created_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)', updated_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)', INDEX IDX_257F9F96A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE signalement (id INT AUTO_INCREMENT NOT NULL, modified_by_id INT DEFAULT NULL, created_by_id INT DEFAULT NULL, closed_by_id INT DEFAULT NULL, territory_id INT DEFAULT NULL, created_from_id INT DEFAULT NULL, bailleur_id INT DEFAULT NULL, uuid VARCHAR(255) NOT NULL, profile_declarant VARCHAR(255) DEFAULT NULL, details LONGTEXT DEFAULT NULL, is_proprio_averti TINYINT(1) DEFAULT NULL, nb_adultes VARCHAR(255) DEFAULT NULL, nb_enfants_m6 VARCHAR(255) DEFAULT NULL, nb_enfants_p6 VARCHAR(255) DEFAULT NULL, is_allocataire VARCHAR(3) DEFAULT NULL, num_allocataire VARCHAR(25) DEFAULT NULL, nature_logement VARCHAR(15) DEFAULT NULL, superficie DOUBLE PRECISION DEFAULT NULL, loyer DOUBLE PRECISION DEFAULT NULL, is_bail_en_cours TINYINT(1) DEFAULT NULL, is_logement_vacant TINYINT(1) DEFAULT NULL, date_entree DATE DEFAULT NULL, type_proprio VARCHAR(255) DEFAULT NULL, nom_proprio VARCHAR(255) DEFAULT NULL, denomination_proprio VARCHAR(255) DEFAULT NULL, prenom_proprio VARCHAR(255) DEFAULT NULL, adresse_proprio VARCHAR(255) DEFAULT NULL, code_postal_proprio VARCHAR(5) DEFAULT NULL, ville_proprio VARCHAR(255) DEFAULT NULL, tel_proprio VARCHAR(128) DEFAULT NULL, tel_proprio_secondaire VARCHAR(128) DEFAULT NULL, mail_proprio VARCHAR(255) DEFAULT NULL, is_logement_social TINYINT(1) DEFAULT NULL, is_preavis_depart TINYINT(1) DEFAULT NULL, is_relogement TINYINT(1) DEFAULT NULL, is_refus_intervention TINYINT(1) DEFAULT NULL, raison_refus_intervention LONGTEXT DEFAULT NULL, is_not_occupant TINYINT(1) DEFAULT NULL, nom_declarant VARCHAR(50) DEFAULT NULL, prenom_declarant VARCHAR(50) DEFAULT NULL, tel_declarant VARCHAR(128) DEFAULT NULL, tel_declarant_secondaire VARCHAR(128) DEFAULT NULL, mail_declarant VARCHAR(255) DEFAULT NULL, structure_declarant VARCHAR(200) DEFAULT NULL, civilite_occupant VARCHAR(10) DEFAULT NULL, nom_occupant VARCHAR(50) DEFAULT NULL, prenom_occupant VARCHAR(50) DEFAULT NULL, tel_occupant VARCHAR(128) DEFAULT NULL, mail_occupant VARCHAR(255) DEFAULT NULL, adresse_occupant VARCHAR(100) DEFAULT NULL, cp_occupant VARCHAR(5) DEFAULT NULL, ville_occupant VARCHAR(100) DEFAULT NULL, ban_id_occupant VARCHAR(50) DEFAULT NULL, nom_agence VARCHAR(255) DEFAULT NULL, denomination_agence VARCHAR(255) DEFAULT NULL, prenom_agence VARCHAR(255) DEFAULT NULL, adresse_agence VARCHAR(255) DEFAULT NULL, code_postal_agence VARCHAR(5) DEFAULT NULL, ville_agence VARCHAR(255) DEFAULT NULL, tel_agence VARCHAR(128) DEFAULT NULL, tel_agence_secondaire VARCHAR(128) DEFAULT NULL, mail_agence VARCHAR(255) DEFAULT NULL, is_cgu_accepted TINYINT(1) NOT NULL, created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', modified_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)', statut VARCHAR(255) NOT NULL, reference VARCHAR(100) NOT NULL, json_content JSON NOT NULL, geoloc JSON NOT NULL, montant_allocation DOUBLE PRECISION DEFAULT NULL, last_suivi_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)', last_suivi_by VARCHAR(255) DEFAULT NULL, code_procedure VARCHAR(255) DEFAULT NULL, etage_occupant VARCHAR(255) DEFAULT NULL, escalier_occupant VARCHAR(255) DEFAULT NULL, num_appart_occupant VARCHAR(255) DEFAULT NULL, adresse_autre_occupant VARCHAR(255) DEFAULT NULL, insee_occupant VARCHAR(255) DEFAULT NULL, manual_address_occupant TINYINT(1) DEFAULT NULL, code_suivi VARCHAR(255) DEFAULT NULL, lien_declarant_occupant VARCHAR(255) DEFAULT NULL, is_consentement_tiers TINYINT(1) DEFAULT NULL, validated_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)', is_rsa TINYINT(1) DEFAULT NULL, annee_construction VARCHAR(255) DEFAULT NULL, type_energie_logement VARCHAR(255) DEFAULT NULL, origine_signalement VARCHAR(255) DEFAULT NULL, situation_occupant VARCHAR(255) DEFAULT NULL, situation_pro_occupant VARCHAR(255) DEFAULT NULL, naissance_occupants VARCHAR(255) DEFAULT NULL, is_logement_collectif TINYINT(1) DEFAULT NULL, is_construction_avant1949 TINYINT(1) DEFAULT NULL, is_diag_socio_technique TINYINT(1) DEFAULT NULL, is_fond_solidarite_logement TINYINT(1) DEFAULT NULL, is_risque_sur_occupation TINYINT(1) DEFAULT NULL, proprio_averti_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)', nom_referent_social VARCHAR(255) DEFAULT NULL, structure_referent_social VARCHAR(255) DEFAULT NULL, numero_invariant VARCHAR(255) DEFAULT NULL, nb_pieces_logement INT DEFAULT NULL, nb_chambres_logement INT DEFAULT NULL, nb_niveaux_logement INT DEFAULT NULL, nb_occupants_logement INT DEFAULT NULL, motif_cloture VARCHAR(255) DEFAULT NULL, motif_refus VARCHAR(255) DEFAULT NULL, closed_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)', tel_occupant_bis VARCHAR(128) DEFAULT NULL, is_imported TINYINT(1) DEFAULT NULL, score DOUBLE PRECISION NOT NULL, score_logement DOUBLE PRECISION NOT NULL, score_batiment DOUBLE PRECISION NOT NULL, is_usager_abandon_procedure TINYINT(1) DEFAULT NULL, date_naissance_occupant DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)', type_composition_logement JSON DEFAULT NULL COMMENT '(DC2Type:type_composition_logement)', situation_foyer JSON DEFAULT NULL COMMENT '(DC2Type:situation_foyer)', information_procedure JSON DEFAULT NULL COMMENT '(DC2Type:information_procedure)', information_complementaire JSON DEFAULT NULL COMMENT '(DC2Type:information_complementaire)', last_suivi_is_public TINYINT(1) DEFAULT NULL, synchro_data JSON DEFAULT NULL, rnb_id_occupant VARCHAR(255) DEFAULT NULL, debut_desordres VARCHAR(15) DEFAULT NULL, has_seen_desordres TINYINT(1) DEFAULT NULL, com_cloture LONGTEXT DEFAULT NULL, INDEX IDX_F4B5511499049ECE (modified_by_id), INDEX IDX_F4B55114B03A8386 (created_by_id), INDEX IDX_F4B55114E1FA7797 (closed_by_id), INDEX IDX_F4B5511473F74AD4 (territory_id), INDEX IDX_F4B551143EA4CB4D (created_from_id), INDEX IDX_F4B5511457B5D0A2 (bailleur_id), INDEX idx_signalement_statut (statut), INDEX idx_signalement_created_at (created_at), INDEX idx_signalement_is_imported (is_imported), INDEX idx_signalement_uuid (uuid), INDEX idx_signalement_code_suivi (code_suivi), INDEX idx_signalement_cp_occupant (cp_occupant), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE signalement_situation (signalement_id INT NOT NULL, situation_id INT NOT NULL, INDEX IDX_E4FA897965C5E57E (signalement_id), INDEX IDX_E4FA89793408E8AF (situation_id), PRIMARY KEY(signalement_id, situation_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE signalement_critere (signalement_id INT NOT NULL, critere_id INT NOT NULL, INDEX IDX_81C2C8A765C5E57E (signalement_id), INDEX IDX_81C2C8A79E5F45AB (critere_id), PRIMARY KEY(signalement_id, critere_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE signalement_criticite (signalement_id INT NOT NULL, criticite_id INT NOT NULL, INDEX IDX_67E4FE2B65C5E57E (signalement_id), INDEX IDX_67E4FE2BC141C0A0 (criticite_id), PRIMARY KEY(signalement_id, criticite_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE tag_signalement (signalement_id INT NOT NULL, tag_id INT NOT NULL, INDEX IDX_E87C2BF465C5E57E (signalement_id), INDEX IDX_E87C2BF4BAD26311 (tag_id), PRIMARY KEY(signalement_id, tag_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE desordre_categorie_signalement (signalement_id INT NOT NULL, desordre_categorie_id INT NOT NULL, INDEX IDX_E365880C65C5E57E (signalement_id), INDEX IDX_E365880CECF01477 (desordre_categorie_id), PRIMARY KEY(signalement_id, desordre_categorie_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE desordre_critere_signalement (signalement_id INT NOT NULL, desordre_critere_id INT NOT NULL, INDEX IDX_689D9BA865C5E57E (signalement_id), INDEX IDX_689D9BA81C3935AB (desordre_critere_id), PRIMARY KEY(signalement_id, desordre_critere_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE desordre_precision_signalement (signalement_id INT NOT NULL, desordre_precision_id INT NOT NULL, INDEX IDX_D390215F65C5E57E (signalement_id), INDEX IDX_D390215F9FB07E9C (desordre_precision_id), PRIMARY KEY(signalement_id, desordre_precision_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE signalement_draft (id INT AUTO_INCREMENT NOT NULL, uuid VARCHAR(255) NOT NULL, profile_declarant VARCHAR(255) DEFAULT NULL, email_declarant VARCHAR(255) NOT NULL, address_complete VARCHAR(255) NOT NULL, payload JSON NOT NULL, current_step VARCHAR(128) NOT NULL, status VARCHAR(255) DEFAULT NULL, checksum VARCHAR(255) DEFAULT NULL, created_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)', updated_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)', PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE signalement_qualification (id INT AUTO_INCREMENT NOT NULL, signalement_id INT NOT NULL, qualification VARCHAR(255) NOT NULL, criticites JSON DEFAULT NULL, desordre_precision_ids JSON DEFAULT NULL, details JSON DEFAULT NULL, status VARCHAR(255) DEFAULT NULL, is_post_visite TINYINT(1) DEFAULT NULL, INDEX IDX_6617D77965C5E57E (signalement_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE signalement_usager (id INT AUTO_INCREMENT NOT NULL, signalement_id INT NOT NULL, declarant_id INT DEFAULT NULL, occupant_id INT DEFAULT NULL, UNIQUE INDEX UNIQ_408FE76765C5E57E (signalement_id), INDEX IDX_408FE767EC439BC (declarant_id), INDEX IDX_408FE76767BAA0E5 (occupant_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE situation (id INT AUTO_INCREMENT NOT NULL, label VARCHAR(255) NOT NULL, menu_label VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', modified_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)', is_active TINYINT(1) NOT NULL, icon VARCHAR(50) DEFAULT NULL, is_archive TINYINT(1) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE suivi (id INT AUTO_INCREMENT NOT NULL, created_by_id INT DEFAULT NULL, signalement_id INT NOT NULL, deleted_by_id INT DEFAULT NULL, created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', description LONGTEXT NOT NULL, is_public TINYINT(1) NOT NULL, type INT NOT NULL, context VARCHAR(100) DEFAULT NULL, deleted_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)', original_data JSON DEFAULT NULL, is_sanitized TINYINT(1) NOT NULL, category VARCHAR(255) DEFAULT NULL COMMENT 'Value possible enum SuiviCategory', INDEX IDX_2EBCCA8FB03A8386 (created_by_id), INDEX IDX_2EBCCA8F65C5E57E (signalement_id), INDEX IDX_2EBCCA8FC76F1F52 (deleted_by_id), INDEX idx_suivi_type (type), INDEX idx_suivi_created_at (created_at), INDEX idx_suivi_signalement_type_created_at (signalement_id, type, created_at), INDEX idx_suivi_context (context), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE tag (id INT AUTO_INCREMENT NOT NULL, territory_id INT NOT NULL, label VARCHAR(255) NOT NULL, is_archive TINYINT(1) NOT NULL, INDEX IDX_389B78373F74AD4 (territory_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE territory (id INT AUTO_INCREMENT NOT NULL, zip VARCHAR(3) NOT NULL, name VARCHAR(255) NOT NULL, is_active TINYINT(1) NOT NULL, bbox JSON NOT NULL, authorized_codes_insee JSON DEFAULT NULL, timezone VARCHAR(128) NOT NULL, grille_visite_filename VARCHAR(255) DEFAULT NULL, is_grille_visite_disabled TINYINT(1) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE user (id INT AUTO_INCREMENT NOT NULL, uuid CHAR(36) NOT NULL COMMENT '(DC2Type:guid)', pro_connect_user_id VARCHAR(255) DEFAULT NULL, email VARCHAR(180) NOT NULL, roles JSON NOT NULL, has_permission_affectation TINYINT(1) NOT NULL, password VARCHAR(255) DEFAULT NULL, token VARCHAR(255) DEFAULT NULL, token_expired_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)', nom VARCHAR(255) DEFAULT NULL, prenom VARCHAR(255) DEFAULT NULL, statut VARCHAR(255) NOT NULL, last_login_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)', is_mailing_active TINYINT(1) NOT NULL, is_mailing_summary TINYINT(1) NOT NULL, is_activate_account_notification_enabled TINYINT(1) NOT NULL, archiving_scheduled_at DATE DEFAULT NULL COMMENT '(DC2Type:date_immutable)', anonymized_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)', auth_code VARCHAR(255) DEFAULT NULL, cgu_version_checked VARCHAR(255) DEFAULT NULL, avatar_filename VARCHAR(255) DEFAULT NULL, temp_email VARCHAR(255) DEFAULT NULL, fonction VARCHAR(255) DEFAULT NULL, created_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)', updated_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)', UNIQUE INDEX UNIQ_8D93D64921CADBFB (pro_connect_user_id), UNIQUE INDEX UNIQ_8D93D649E7927C74 (email), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE user_partner (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, partner_id INT NOT NULL, created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', INDEX IDX_6926201CA76ED395 (user_id), INDEX IDX_6926201C9393F8FE (partner_id), UNIQUE INDEX unique_user_partner (user_id, partner_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE zone (id INT AUTO_INCREMENT NOT NULL, territory_id INT NOT NULL, created_by_id INT NOT NULL, area LONGTEXT NOT NULL, name VARCHAR(255) NOT NULL, type VARCHAR(255) NOT NULL COMMENT 'Value possible enum ZoneType', created_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)', updated_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)', INDEX IDX_A0EBC00773F74AD4 (territory_id), INDEX IDX_A0EBC007B03A8386 (created_by_id), UNIQUE INDEX UNIQ_A0EBC0075E237E0673F74AD4 (name, territory_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE affectation ADD CONSTRAINT FK_F4DD61D365C5E57E FOREIGN KEY (signalement_id) REFERENCES signalement (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE affectation ADD CONSTRAINT FK_F4DD61D39393F8FE FOREIGN KEY (partner_id) REFERENCES partner (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE affectation ADD CONSTRAINT FK_F4DD61D32FC55A77 FOREIGN KEY (answered_by_id) REFERENCES user (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE affectation ADD CONSTRAINT FK_F4DD61D369E36731 FOREIGN KEY (affected_by_id) REFERENCES user (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE affectation ADD CONSTRAINT FK_F4DD61D373F74AD4 FOREIGN KEY (territory_id) REFERENCES territory (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE api_user_token ADD CONSTRAINT FK_7A5F26725E70BCD7 FOREIGN KEY (owned_by_id) REFERENCES user (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE auto_affectation_rule ADD CONSTRAINT FK_1A302A1C73F74AD4 FOREIGN KEY (territory_id) REFERENCES territory (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE bailleur_territory ADD CONSTRAINT FK_7A87051F57B5D0A2 FOREIGN KEY (bailleur_id) REFERENCES bailleur (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE bailleur_territory ADD CONSTRAINT FK_7A87051F73F74AD4 FOREIGN KEY (territory_id) REFERENCES territory (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE commune ADD CONSTRAINT FK_E2E2D1EE73F74AD4 FOREIGN KEY (territory_id) REFERENCES territory (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE commune ADD CONSTRAINT FK_E2E2D1EE4E7C18CB FOREIGN KEY (epci_id) REFERENCES epci (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE critere ADD CONSTRAINT FK_7F6A80533408E8AF FOREIGN KEY (situation_id) REFERENCES situation (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE criticite ADD CONSTRAINT FK_6F33ED989E5F45AB FOREIGN KEY (critere_id) REFERENCES critere (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE desordre_critere ADD CONSTRAINT FK_6DCB3D10ECF01477 FOREIGN KEY (desordre_categorie_id) REFERENCES desordre_categorie (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE desordre_precision ADD CONSTRAINT FK_23744FA01C3935AB FOREIGN KEY (desordre_critere_id) REFERENCES desordre_critere (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE file ADD CONSTRAINT FK_8C9F3610A2B28FE8 FOREIGN KEY (uploaded_by_id) REFERENCES user (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE file ADD CONSTRAINT FK_8C9F361065C5E57E FOREIGN KEY (signalement_id) REFERENCES signalement (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE file ADD CONSTRAINT FK_8C9F36108EAE3863 FOREIGN KEY (intervention_id) REFERENCES intervention (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE history_entry ADD CONSTRAINT FK_7299951765C5E57E FOREIGN KEY (signalement_id) REFERENCES signalement (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE history_entry ADD CONSTRAINT FK_72999517A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE intervention ADD CONSTRAINT FK_D11814AB65C5E57E FOREIGN KEY (signalement_id) REFERENCES signalement (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE intervention ADD CONSTRAINT FK_D11814AB9393F8FE FOREIGN KEY (partner_id) REFERENCES partner (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE notification ADD CONSTRAINT FK_BF5476CAA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE notification ADD CONSTRAINT FK_BF5476CA65C5E57E FOREIGN KEY (signalement_id) REFERENCES signalement (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE notification ADD CONSTRAINT FK_BF5476CA7FEA59C0 FOREIGN KEY (suivi_id) REFERENCES suivi (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE notification ADD CONSTRAINT FK_BF5476CA6D0ABA22 FOREIGN KEY (affectation_id) REFERENCES affectation (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE partner ADD CONSTRAINT FK_312B3E1673F74AD4 FOREIGN KEY (territory_id) REFERENCES territory (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE partner ADD CONSTRAINT FK_312B3E1657B5D0A2 FOREIGN KEY (bailleur_id) REFERENCES bailleur (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE partner_zone ADD CONSTRAINT FK_F0DFF31F9393F8FE FOREIGN KEY (partner_id) REFERENCES partner (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE partner_zone ADD CONSTRAINT FK_F0DFF31F9F2C3FAB FOREIGN KEY (zone_id) REFERENCES zone (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE partner_excluded_zone ADD CONSTRAINT FK_EF69B0979393F8FE FOREIGN KEY (partner_id) REFERENCES partner (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE partner_excluded_zone ADD CONSTRAINT FK_EF69B0979F2C3FAB FOREIGN KEY (zone_id) REFERENCES zone (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE pop_notification ADD CONSTRAINT FK_257F9F96A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE signalement ADD CONSTRAINT FK_F4B5511499049ECE FOREIGN KEY (modified_by_id) REFERENCES user (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE signalement ADD CONSTRAINT FK_F4B55114B03A8386 FOREIGN KEY (created_by_id) REFERENCES user (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE signalement ADD CONSTRAINT FK_F4B55114E1FA7797 FOREIGN KEY (closed_by_id) REFERENCES user (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE signalement ADD CONSTRAINT FK_F4B5511473F74AD4 FOREIGN KEY (territory_id) REFERENCES territory (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE signalement ADD CONSTRAINT FK_F4B551143EA4CB4D FOREIGN KEY (created_from_id) REFERENCES signalement_draft (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE signalement ADD CONSTRAINT FK_F4B5511457B5D0A2 FOREIGN KEY (bailleur_id) REFERENCES bailleur (id) ON DELETE SET NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE signalement_situation ADD CONSTRAINT FK_E4FA897965C5E57E FOREIGN KEY (signalement_id) REFERENCES signalement (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE signalement_situation ADD CONSTRAINT FK_E4FA89793408E8AF FOREIGN KEY (situation_id) REFERENCES situation (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE signalement_critere ADD CONSTRAINT FK_81C2C8A765C5E57E FOREIGN KEY (signalement_id) REFERENCES signalement (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE signalement_critere ADD CONSTRAINT FK_81C2C8A79E5F45AB FOREIGN KEY (critere_id) REFERENCES critere (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE signalement_criticite ADD CONSTRAINT FK_67E4FE2B65C5E57E FOREIGN KEY (signalement_id) REFERENCES signalement (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE signalement_criticite ADD CONSTRAINT FK_67E4FE2BC141C0A0 FOREIGN KEY (criticite_id) REFERENCES criticite (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE tag_signalement ADD CONSTRAINT FK_E87C2BF465C5E57E FOREIGN KEY (signalement_id) REFERENCES signalement (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE tag_signalement ADD CONSTRAINT FK_E87C2BF4BAD26311 FOREIGN KEY (tag_id) REFERENCES tag (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE desordre_categorie_signalement ADD CONSTRAINT FK_E365880C65C5E57E FOREIGN KEY (signalement_id) REFERENCES signalement (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE desordre_categorie_signalement ADD CONSTRAINT FK_E365880CECF01477 FOREIGN KEY (desordre_categorie_id) REFERENCES desordre_categorie (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE desordre_critere_signalement ADD CONSTRAINT FK_689D9BA865C5E57E FOREIGN KEY (signalement_id) REFERENCES signalement (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE desordre_critere_signalement ADD CONSTRAINT FK_689D9BA81C3935AB FOREIGN KEY (desordre_critere_id) REFERENCES desordre_critere (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE desordre_precision_signalement ADD CONSTRAINT FK_D390215F65C5E57E FOREIGN KEY (signalement_id) REFERENCES signalement (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE desordre_precision_signalement ADD CONSTRAINT FK_D390215F9FB07E9C FOREIGN KEY (desordre_precision_id) REFERENCES desordre_precision (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE signalement_qualification ADD CONSTRAINT FK_6617D77965C5E57E FOREIGN KEY (signalement_id) REFERENCES signalement (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE signalement_usager ADD CONSTRAINT FK_408FE76765C5E57E FOREIGN KEY (signalement_id) REFERENCES signalement (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE signalement_usager ADD CONSTRAINT FK_408FE767EC439BC FOREIGN KEY (declarant_id) REFERENCES user (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE signalement_usager ADD CONSTRAINT FK_408FE76767BAA0E5 FOREIGN KEY (occupant_id) REFERENCES user (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE suivi ADD CONSTRAINT FK_2EBCCA8FB03A8386 FOREIGN KEY (created_by_id) REFERENCES user (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE suivi ADD CONSTRAINT FK_2EBCCA8F65C5E57E FOREIGN KEY (signalement_id) REFERENCES signalement (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE suivi ADD CONSTRAINT FK_2EBCCA8FC76F1F52 FOREIGN KEY (deleted_by_id) REFERENCES user (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE tag ADD CONSTRAINT FK_389B78373F74AD4 FOREIGN KEY (territory_id) REFERENCES territory (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user_partner ADD CONSTRAINT FK_6926201CA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user_partner ADD CONSTRAINT FK_6926201C9393F8FE FOREIGN KEY (partner_id) REFERENCES partner (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE zone ADD CONSTRAINT FK_A0EBC00773F74AD4 FOREIGN KEY (territory_id) REFERENCES territory (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE zone ADD CONSTRAINT FK_A0EBC007B03A8386 FOREIGN KEY (created_by_id) REFERENCES user (id)
        SQL);
        $this->addSql('
            CREATE OR REPLACE VIEW view_latest_intervention AS
                SELECT
                    i.signalement_id,
                    i.conclude_procedure,
                    i.details,
                    i.occupant_present,
                    i.scheduled_at,
                    i.status,
                    (
                        SELECT COUNT(*)
                        FROM intervention i2
                        WHERE i2.signalement_id = i.signalement_id
                          AND i2.type = \'VISITE\'
                    ) AS nb_visites
                FROM
                    intervention i
                WHERE
                    i.type = \'VISITE\' AND
                    i.scheduled_at = (
                        SELECT
                            MAX(i2.scheduled_at)
                        FROM
                            intervention i2
                        WHERE
                            i2.signalement_id = i.signalement_id
                            AND i2.type = \'VISITE\'
                    );'
        );
    }

    public function down(Schema $schema): void
    {
    }
}
