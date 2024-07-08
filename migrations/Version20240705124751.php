<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20240705124751 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add table AutoAffectationRule';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(
            'CREATE TABLE auto_affectation_rule (
            id INT AUTO_INCREMENT NOT NULL,
            territory_id INT DEFAULT NULL,
            status VARCHAR(255) NOT NULL COMMENT \'Value possible ACTIVE or ARCHIVED\',
            partner_type VARCHAR(255) NOT NULL COMMENT \'Value possible enum PartnerType\',
            profile_declarant VARCHAR(255) NOT NULL COMMENT \'Value possible enum ProfileDeclarant or all, tiers or occupant\',
            insee_to_include VARCHAR(255) NOT NULL COMMENT \'Value possible all, partner_list or an array of code insee\',
            insee_to_exclude JSON DEFAULT NULL COMMENT \'Value possible null or an array of code insee\',
            parc VARCHAR(32) NOT NULL COMMENT \'Value possible all, prive or public\',
            allocataire VARCHAR(32) NOT NULL COMMENT \'Value possible all, non, oui, caf or msa\',
            INDEX IDX_1A302A1C73F74AD4 (territory_id),
            PRIMARY KEY(id))
            DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB'
        );
        $this->addSql('ALTER TABLE auto_affectation_rule ADD CONSTRAINT FK_1A302A1C73F74AD4 FOREIGN KEY (territory_id) REFERENCES territory (id)');

        // $this->addSql('
        //     INSERT INTO auto_affectation_rule (territory_id, status, profile_declarant, partner_type, insee_to_include, insee_to_exclude, parc, allocataire)
        //     VALUES (94, "ACTIVE", "all", "COMMUNE_SCHS", "partner_list", null, "all", "all")
        // ');
        // $this->addSql('
        //     INSERT INTO auto_affectation_rule (territory_id, status, profile_declarant, partner_type, insee_to_include, insee_to_exclude, parc, allocataire)
        //     VALUES (35, "ACTIVE", "all", "COMMUNE_SCHS", "partner_list", JSON_ARRAY("34500", "34600"), "prive", "all")
        // ');
        // $this->addSql('
        //     INSERT INTO auto_affectation_rule (territory_id, status, profile_declarant, partner_type, insee_to_include, insee_to_exclude, parc, allocataire)
        //     VALUES (35, "ACTIVE", "all", "CAF_MSA", "all", JSON_ARRAY("34500", "34600"), "prive", "all")
        // ');
        // $this->addSql('
        //     INSERT INTO auto_affectation_rule (territory_id, status, profile_declarant, partner_type, insee_to_include, insee_to_exclude, parc, allocataire)
        //     VALUES (35, "ACTIVE", "all", "CONSEIL_DEPARTEMENTAL", "all", JSON_ARRAY("34500", "34600"), "prive", "all")
        // ');
        // $this->addSql('
        //     INSERT INTO auto_affectation_rule (territory_id, status, profile_declarant, partner_type, insee_to_include, insee_to_exclude, parc, allocataire)
        //     VALUES (35, "ACTIVE", "all", "EPCI", "partner_list", null, "prive", "all")
        // ');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE auto_affectation_rule DROP FOREIGN KEY FK_1A302A1C73F74AD4');
        $this->addSql('DROP TABLE auto_affectation_rule');
    }
}
