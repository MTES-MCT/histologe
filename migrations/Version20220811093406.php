<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220811093406 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE affectation (id INT AUTO_INCREMENT NOT NULL, signalement_id INT NOT NULL, partner_id INT NOT NULL, answered_by_id INT DEFAULT NULL, affected_by_id INT DEFAULT NULL, territory_id INT DEFAULT NULL, answered_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', statut INT NOT NULL, motif_cloture VARCHAR(255) DEFAULT NULL, INDEX IDX_F4DD61D365C5E57E (signalement_id), INDEX IDX_F4DD61D39393F8FE (partner_id), INDEX IDX_F4DD61D32FC55A77 (answered_by_id), INDEX IDX_F4DD61D369E36731 (affected_by_id), INDEX IDX_F4DD61D373F74AD4 (territory_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE config (id INT AUTO_INCREMENT NOT NULL, nom_territoire VARCHAR(255) NOT NULL, url_territoire VARCHAR(255) NOT NULL, nom_dpo VARCHAR(255) NOT NULL, mail_dpo VARCHAR(255) NOT NULL, nom_responsable VARCHAR(255) NOT NULL, mail_responsable VARCHAR(255) NOT NULL, adresse_dpo VARCHAR(255) NOT NULL, logotype VARCHAR(255) DEFAULT NULL, email_reponse VARCHAR(255) DEFAULT NULL, tracking_code LONGTEXT DEFAULT NULL, tag_manager_code LONGTEXT DEFAULT NULL, mail_ar LONGTEXT DEFAULT NULL, mail_validation LONGTEXT DEFAULT NULL, esabora_url VARCHAR(255) DEFAULT NULL, esabora_token VARCHAR(255) DEFAULT NULL, tel_contact VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE critere (id INT AUTO_INCREMENT NOT NULL, situation_id INT NOT NULL, label VARCHAR(255) NOT NULL, description LONGTEXT NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', modified_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', is_archive TINYINT(1) NOT NULL, is_danger TINYINT(1) NOT NULL, coef INT NOT NULL, INDEX IDX_7F6A80533408E8AF (situation_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE criticite (id INT AUTO_INCREMENT NOT NULL, critere_id INT NOT NULL, label LONGTEXT NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', modified_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', score INT NOT NULL, is_archive TINYINT(1) NOT NULL, is_default TINYINT(1) NOT NULL, INDEX IDX_6F33ED989E5F45AB (critere_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE notification (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, signalement_id INT DEFAULT NULL, suivi_id INT DEFAULT NULL, affectation_id INT DEFAULT NULL, is_seen TINYINT(1) NOT NULL, type INT NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_BF5476CAA76ED395 (user_id), INDEX IDX_BF5476CA65C5E57E (signalement_id), INDEX IDX_BF5476CA7FEA59C0 (suivi_id), INDEX IDX_BF5476CA6D0ABA22 (affectation_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE partner (id INT AUTO_INCREMENT NOT NULL, territory_id INT DEFAULT NULL, nom VARCHAR(255) NOT NULL, is_archive TINYINT(1) NOT NULL, is_commune TINYINT(1) NOT NULL, insee JSON NOT NULL, email VARCHAR(100) DEFAULT NULL, esabora_url VARCHAR(255) DEFAULT NULL, esabora_token VARCHAR(255) DEFAULT NULL, INDEX IDX_312B3E1673F74AD4 (territory_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE signalement (id INT AUTO_INCREMENT NOT NULL, modified_by_id INT DEFAULT NULL, territory_id INT DEFAULT NULL, uuid VARCHAR(255) NOT NULL, details LONGTEXT NOT NULL, photos JSON DEFAULT NULL, documents JSON DEFAULT NULL, is_proprio_averti TINYINT(1) DEFAULT NULL, nb_adultes VARCHAR(255) DEFAULT NULL, nb_enfants_m6 VARCHAR(255) DEFAULT NULL, nb_enfants_p6 VARCHAR(255) DEFAULT NULL, is_allocataire VARCHAR(3) DEFAULT NULL, num_allocataire VARCHAR(25) DEFAULT NULL, nature_logement VARCHAR(15) DEFAULT NULL, type_logement VARCHAR(15) DEFAULT NULL, superficie DOUBLE PRECISION DEFAULT NULL, loyer DOUBLE PRECISION DEFAULT NULL, is_bail_en_cours TINYINT(1) DEFAULT NULL, date_entree DATE DEFAULT NULL, nom_proprio VARCHAR(255) DEFAULT NULL, adresse_proprio VARCHAR(255) DEFAULT NULL, tel_proprio VARCHAR(15) DEFAULT NULL, mail_proprio VARCHAR(255) DEFAULT NULL, is_logement_social TINYINT(1) DEFAULT NULL, is_preavis_depart TINYINT(1) DEFAULT NULL, is_relogement TINYINT(1) DEFAULT NULL, is_refus_intervention TINYINT(1) DEFAULT NULL, raison_refus_intervention LONGTEXT DEFAULT NULL, is_not_occupant TINYINT(1) DEFAULT NULL, nom_declarant VARCHAR(50) DEFAULT NULL, prenom_declarant VARCHAR(50) DEFAULT NULL, tel_declarant VARCHAR(15) DEFAULT NULL, mail_declarant VARCHAR(50) DEFAULT NULL, structure_declarant VARCHAR(50) DEFAULT NULL, nom_occupant VARCHAR(50) NOT NULL, prenom_occupant VARCHAR(50) NOT NULL, tel_occupant VARCHAR(15) DEFAULT NULL, mail_occupant VARCHAR(50) DEFAULT NULL, adresse_occupant VARCHAR(100) NOT NULL, cp_occupant VARCHAR(5) NOT NULL, ville_occupant VARCHAR(100) NOT NULL, is_cgu_accepted TINYINT(1) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', modified_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', statut INT NOT NULL, reference VARCHAR(100) NOT NULL, json_content JSON NOT NULL, geoloc JSON NOT NULL, date_visite DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', is_occupant_present_visite TINYINT(1) DEFAULT NULL, montant_allocation DOUBLE PRECISION DEFAULT NULL, is_situation_handicap TINYINT(1) DEFAULT NULL, code_procedure VARCHAR(255) DEFAULT NULL, score_creation DOUBLE PRECISION NOT NULL, score_cloture DOUBLE PRECISION DEFAULT NULL, etage_occupant VARCHAR(255) DEFAULT NULL, escalier_occupant VARCHAR(255) DEFAULT NULL, num_appart_occupant VARCHAR(255) DEFAULT NULL, adresse_autre_occupant VARCHAR(255) DEFAULT NULL, mode_contact_proprio JSON DEFAULT NULL, insee_occupant VARCHAR(255) DEFAULT NULL, code_suivi VARCHAR(255) DEFAULT NULL, lien_declarant_occupant VARCHAR(255) DEFAULT NULL, is_consentement_tiers TINYINT(1) DEFAULT NULL, validated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', is_rsa TINYINT(1) DEFAULT NULL, prorio_averti_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', annee_construction INT DEFAULT NULL, type_energie_logement VARCHAR(50) DEFAULT NULL, origine_signalement VARCHAR(255) DEFAULT NULL, situation_occupant VARCHAR(255) DEFAULT NULL, situation_pro_occupant VARCHAR(255) DEFAULT NULL, naissance_occupant_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', is_logement_collectif TINYINT(1) DEFAULT NULL, is_construction_avant1949 TINYINT(1) DEFAULT NULL, is_diag_socio_technique TINYINT(1) DEFAULT NULL, is_fond_solidarite_logement TINYINT(1) DEFAULT NULL, is_risque_sur_occupation TINYINT(1) DEFAULT NULL, proprio_averti_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', nom_referent_social VARCHAR(255) DEFAULT NULL, structure_referent_social VARCHAR(255) DEFAULT NULL, mail_syndic VARCHAR(255) DEFAULT NULL, nom_sci VARCHAR(255) DEFAULT NULL, nom_representant_sci VARCHAR(255) DEFAULT NULL, tel_sci VARCHAR(12) DEFAULT NULL, mail_sci VARCHAR(255) DEFAULT NULL, tel_syndic VARCHAR(12) DEFAULT NULL, nom_syndic VARCHAR(255) DEFAULT NULL, numero_invariant VARCHAR(255) DEFAULT NULL, nb_pieces_logement INT DEFAULT NULL, nb_chambres_logement INT DEFAULT NULL, nb_niveaux_logement INT DEFAULT NULL, nb_occupants_logement INT DEFAULT NULL, motif_cloture VARCHAR(255) DEFAULT NULL, closed_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', tel_occupant_bis VARCHAR(15) DEFAULT NULL, INDEX IDX_F4B5511499049ECE (modified_by_id), INDEX IDX_F4B5511473F74AD4 (territory_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE signalement_situation (signalement_id INT NOT NULL, situation_id INT NOT NULL, INDEX IDX_E4FA897965C5E57E (signalement_id), INDEX IDX_E4FA89793408E8AF (situation_id), PRIMARY KEY(signalement_id, situation_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE signalement_critere (signalement_id INT NOT NULL, critere_id INT NOT NULL, INDEX IDX_81C2C8A765C5E57E (signalement_id), INDEX IDX_81C2C8A79E5F45AB (critere_id), PRIMARY KEY(signalement_id, critere_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE signalement_criticite (signalement_id INT NOT NULL, criticite_id INT NOT NULL, INDEX IDX_67E4FE2B65C5E57E (signalement_id), INDEX IDX_67E4FE2BC141C0A0 (criticite_id), PRIMARY KEY(signalement_id, criticite_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE situation (id INT AUTO_INCREMENT NOT NULL, label VARCHAR(255) NOT NULL, menu_label VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', modified_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', is_active TINYINT(1) NOT NULL, icon VARCHAR(50) DEFAULT NULL, is_archive TINYINT(1) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE suivi (id INT AUTO_INCREMENT NOT NULL, created_by_id INT DEFAULT NULL, signalement_id INT NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', description LONGTEXT NOT NULL, is_public TINYINT(1) NOT NULL, INDEX IDX_2EBCCA8FB03A8386 (created_by_id), INDEX IDX_2EBCCA8F65C5E57E (signalement_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE tag (id INT AUTO_INCREMENT NOT NULL, territory_id INT NOT NULL, label VARCHAR(255) NOT NULL, is_archive TINYINT(1) NOT NULL, INDEX IDX_389B78373F74AD4 (territory_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE tag_signalement (tag_id INT NOT NULL, signalement_id INT NOT NULL, INDEX IDX_E87C2BF4BAD26311 (tag_id), INDEX IDX_E87C2BF465C5E57E (signalement_id), PRIMARY KEY(tag_id, signalement_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE territory (id INT AUTO_INCREMENT NOT NULL, config_id INT DEFAULT NULL, zip VARCHAR(3) NOT NULL, name VARCHAR(255) NOT NULL, is_active TINYINT(1) NOT NULL, bbox JSON NOT NULL, UNIQUE INDEX UNIQ_E974396624DB0683 (config_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE user (id INT AUTO_INCREMENT NOT NULL, partner_id INT DEFAULT NULL, territory_id INT DEFAULT NULL, email VARCHAR(180) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) DEFAULT NULL, nom VARCHAR(255) DEFAULT NULL, prenom VARCHAR(255) DEFAULT NULL, statut INT NOT NULL, last_login_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', is_generique TINYINT(1) NOT NULL, is_mailing_active TINYINT(1) NOT NULL, INDEX IDX_8D93D6499393F8FE (partner_id), INDEX IDX_8D93D64973F74AD4 (territory_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE affectation ADD CONSTRAINT FK_F4DD61D365C5E57E FOREIGN KEY (signalement_id) REFERENCES signalement (id)');
        $this->addSql('ALTER TABLE affectation ADD CONSTRAINT FK_F4DD61D39393F8FE FOREIGN KEY (partner_id) REFERENCES partner (id)');
        $this->addSql('ALTER TABLE affectation ADD CONSTRAINT FK_F4DD61D32FC55A77 FOREIGN KEY (answered_by_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE affectation ADD CONSTRAINT FK_F4DD61D369E36731 FOREIGN KEY (affected_by_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE affectation ADD CONSTRAINT FK_F4DD61D373F74AD4 FOREIGN KEY (territory_id) REFERENCES territory (id)');
        $this->addSql('ALTER TABLE critere ADD CONSTRAINT FK_7F6A80533408E8AF FOREIGN KEY (situation_id) REFERENCES situation (id)');
        $this->addSql('ALTER TABLE criticite ADD CONSTRAINT FK_6F33ED989E5F45AB FOREIGN KEY (critere_id) REFERENCES critere (id)');
        $this->addSql('ALTER TABLE notification ADD CONSTRAINT FK_BF5476CAA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE notification ADD CONSTRAINT FK_BF5476CA65C5E57E FOREIGN KEY (signalement_id) REFERENCES signalement (id)');
        $this->addSql('ALTER TABLE notification ADD CONSTRAINT FK_BF5476CA7FEA59C0 FOREIGN KEY (suivi_id) REFERENCES suivi (id)');
        $this->addSql('ALTER TABLE notification ADD CONSTRAINT FK_BF5476CA6D0ABA22 FOREIGN KEY (affectation_id) REFERENCES affectation (id)');
        $this->addSql('ALTER TABLE partner ADD CONSTRAINT FK_312B3E1673F74AD4 FOREIGN KEY (territory_id) REFERENCES territory (id)');
        $this->addSql('ALTER TABLE signalement ADD CONSTRAINT FK_F4B5511499049ECE FOREIGN KEY (modified_by_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE signalement ADD CONSTRAINT FK_F4B5511473F74AD4 FOREIGN KEY (territory_id) REFERENCES territory (id)');
        $this->addSql('ALTER TABLE signalement_situation ADD CONSTRAINT FK_E4FA897965C5E57E FOREIGN KEY (signalement_id) REFERENCES signalement (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE signalement_situation ADD CONSTRAINT FK_E4FA89793408E8AF FOREIGN KEY (situation_id) REFERENCES situation (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE signalement_critere ADD CONSTRAINT FK_81C2C8A765C5E57E FOREIGN KEY (signalement_id) REFERENCES signalement (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE signalement_critere ADD CONSTRAINT FK_81C2C8A79E5F45AB FOREIGN KEY (critere_id) REFERENCES critere (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE signalement_criticite ADD CONSTRAINT FK_67E4FE2B65C5E57E FOREIGN KEY (signalement_id) REFERENCES signalement (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE signalement_criticite ADD CONSTRAINT FK_67E4FE2BC141C0A0 FOREIGN KEY (criticite_id) REFERENCES criticite (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE suivi ADD CONSTRAINT FK_2EBCCA8FB03A8386 FOREIGN KEY (created_by_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE suivi ADD CONSTRAINT FK_2EBCCA8F65C5E57E FOREIGN KEY (signalement_id) REFERENCES signalement (id)');
        $this->addSql('ALTER TABLE tag ADD CONSTRAINT FK_389B78373F74AD4 FOREIGN KEY (territory_id) REFERENCES territory (id)');
        $this->addSql('ALTER TABLE tag_signalement ADD CONSTRAINT FK_E87C2BF4BAD26311 FOREIGN KEY (tag_id) REFERENCES tag (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tag_signalement ADD CONSTRAINT FK_E87C2BF465C5E57E FOREIGN KEY (signalement_id) REFERENCES signalement (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE territory ADD CONSTRAINT FK_E974396624DB0683 FOREIGN KEY (config_id) REFERENCES config (id)');
        $this->addSql('ALTER TABLE user ADD CONSTRAINT FK_8D93D6499393F8FE FOREIGN KEY (partner_id) REFERENCES partner (id)');
        $this->addSql('ALTER TABLE user ADD CONSTRAINT FK_8D93D64973F74AD4 FOREIGN KEY (territory_id) REFERENCES territory (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE notification DROP FOREIGN KEY FK_BF5476CA6D0ABA22');
        $this->addSql('ALTER TABLE territory DROP FOREIGN KEY FK_E974396624DB0683');
        $this->addSql('ALTER TABLE criticite DROP FOREIGN KEY FK_6F33ED989E5F45AB');
        $this->addSql('ALTER TABLE signalement_critere DROP FOREIGN KEY FK_81C2C8A79E5F45AB');
        $this->addSql('ALTER TABLE signalement_criticite DROP FOREIGN KEY FK_67E4FE2BC141C0A0');
        $this->addSql('ALTER TABLE affectation DROP FOREIGN KEY FK_F4DD61D39393F8FE');
        $this->addSql('ALTER TABLE user DROP FOREIGN KEY FK_8D93D6499393F8FE');
        $this->addSql('ALTER TABLE affectation DROP FOREIGN KEY FK_F4DD61D365C5E57E');
        $this->addSql('ALTER TABLE notification DROP FOREIGN KEY FK_BF5476CA65C5E57E');
        $this->addSql('ALTER TABLE signalement_situation DROP FOREIGN KEY FK_E4FA897965C5E57E');
        $this->addSql('ALTER TABLE signalement_critere DROP FOREIGN KEY FK_81C2C8A765C5E57E');
        $this->addSql('ALTER TABLE signalement_criticite DROP FOREIGN KEY FK_67E4FE2B65C5E57E');
        $this->addSql('ALTER TABLE suivi DROP FOREIGN KEY FK_2EBCCA8F65C5E57E');
        $this->addSql('ALTER TABLE tag_signalement DROP FOREIGN KEY FK_E87C2BF465C5E57E');
        $this->addSql('ALTER TABLE critere DROP FOREIGN KEY FK_7F6A80533408E8AF');
        $this->addSql('ALTER TABLE signalement_situation DROP FOREIGN KEY FK_E4FA89793408E8AF');
        $this->addSql('ALTER TABLE notification DROP FOREIGN KEY FK_BF5476CA7FEA59C0');
        $this->addSql('ALTER TABLE tag_signalement DROP FOREIGN KEY FK_E87C2BF4BAD26311');
        $this->addSql('ALTER TABLE affectation DROP FOREIGN KEY FK_F4DD61D373F74AD4');
        $this->addSql('ALTER TABLE partner DROP FOREIGN KEY FK_312B3E1673F74AD4');
        $this->addSql('ALTER TABLE signalement DROP FOREIGN KEY FK_F4B5511473F74AD4');
        $this->addSql('ALTER TABLE tag DROP FOREIGN KEY FK_389B78373F74AD4');
        $this->addSql('ALTER TABLE user DROP FOREIGN KEY FK_8D93D64973F74AD4');
        $this->addSql('ALTER TABLE affectation DROP FOREIGN KEY FK_F4DD61D32FC55A77');
        $this->addSql('ALTER TABLE affectation DROP FOREIGN KEY FK_F4DD61D369E36731');
        $this->addSql('ALTER TABLE notification DROP FOREIGN KEY FK_BF5476CAA76ED395');
        $this->addSql('ALTER TABLE signalement DROP FOREIGN KEY FK_F4B5511499049ECE');
        $this->addSql('ALTER TABLE suivi DROP FOREIGN KEY FK_2EBCCA8FB03A8386');
        $this->addSql('DROP TABLE affectation');
        $this->addSql('DROP TABLE config');
        $this->addSql('DROP TABLE critere');
        $this->addSql('DROP TABLE criticite');
        $this->addSql('DROP TABLE notification');
        $this->addSql('DROP TABLE partner');
        $this->addSql('DROP TABLE signalement');
        $this->addSql('DROP TABLE signalement_situation');
        $this->addSql('DROP TABLE signalement_critere');
        $this->addSql('DROP TABLE signalement_criticite');
        $this->addSql('DROP TABLE situation');
        $this->addSql('DROP TABLE suivi');
        $this->addSql('DROP TABLE tag');
        $this->addSql('DROP TABLE tag_signalement');
        $this->addSql('DROP TABLE territory');
        $this->addSql('DROP TABLE user');
    }
}
