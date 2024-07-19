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
            parc VARCHAR(32) NOT NULL COMMENT \'Value possible all, non_renseigne, prive or public\',
            allocataire VARCHAR(32) NOT NULL COMMENT \'Value possible all, non, oui, caf or msa\',
            INDEX IDX_1A302A1C73F74AD4 (territory_id),
            PRIMARY KEY(id))
            DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB'
        );
        $this->addSql('ALTER TABLE auto_affectation_rule ADD CONSTRAINT FK_1A302A1C73F74AD4 FOREIGN KEY (territory_id) REFERENCES territory (id)');
        $this->addSql('ALTER TABLE territory DROP is_auto_affectation_enabled');

        $territoryId = $this->connection->fetchOne('SELECT id FROM territory WHERE zip = 93');
        if ($territoryId) {
            $this->addSql(
                'INSERT INTO auto_affectation_rule (
                    territory_id, status, partner_type, profile_declarant, insee_to_include, insee_to_exclude, parc, allocataire
                ) VALUES (
                    :territory_id, :status, :partner_type, :profile_declarant, :insee_to_include, :insee_to_exclude, :parc, :allocataire
                )',
                [
                    'territory_id' => $territoryId,
                    'status' => 'ACTIVE',
                    'partner_type' => 'COMMUNE_SCHS',
                    'profile_declarant' => 'all',
                    'insee_to_include' => 'partner_list',
                    'insee_to_exclude' => null,
                    'parc' => 'all',
                    'allocataire' => 'all',
                ]
            );
        }
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE auto_affectation_rule DROP FOREIGN KEY FK_1A302A1C73F74AD4');
        $this->addSql('DROP TABLE auto_affectation_rule');
        $this->addSql('ALTER TABLE territory ADD is_auto_affectation_enabled TINYINT(1) NOT NULL');
    }
}
