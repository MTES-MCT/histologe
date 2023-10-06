<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20230913125858 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add fields to move signalement draft to signalement';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE signalement ADD profile_declarant VARCHAR(255) DEFAULT NULL, ADD prenom_proprio VARCHAR(255) DEFAULT NULL, ADD code_postal_proprio VARCHAR(5) DEFAULT NULL, ADD ville_proprio VARCHAR(255) DEFAULT NULL, ADD tel_proprio_secondaire VARCHAR(15) DEFAULT NULL, ADD tel_declarant_secondaire VARCHAR(15) DEFAULT NULL, ADD civilite_occupant VARCHAR(10) DEFAULT NULL');
        $this->addSql('ALTER TABLE signalement ADD type_composition_logement JSON DEFAULT NULL COMMENT \'(DC2Type:type_composition_logement)\'');
        $this->addSql('ALTER TABLE signalement ADD situation_foyer JSON DEFAULT NULL COMMENT \'(DC2Type:situation_foyer)\'');
        $this->addSql('ALTER TABLE signalement ADD information_procedure JSON DEFAULT NULL COMMENT \'(DC2Type:information_procedure)\'');
        $this->addSql('ALTER TABLE signalement ADD information_complementaire JSON DEFAULT NULL COMMENT \'(DC2Type:information_complementaire)\'');
        $this->addSql('ALTER TABLE signalement CHANGE tel_proprio tel_proprio VARCHAR(128) DEFAULT NULL, CHANGE tel_declarant tel_declarant VARCHAR(128) DEFAULT NULL, CHANGE tel_occupant tel_occupant VARCHAR(128) DEFAULT NULL, CHANGE tel_occupant_bis tel_occupant_bis VARCHAR(128) DEFAULT NULL, CHANGE tel_proprio_secondaire tel_proprio_secondaire VARCHAR(128) DEFAULT NULL, CHANGE tel_declarant_secondaire tel_declarant_secondaire VARCHAR(128) DEFAULT NULL');
        $this->addSql('ALTER TABLE signalement CHANGE nom_occupant nom_occupant VARCHAR(50) DEFAULT NULL, CHANGE prenom_occupant prenom_occupant VARCHAR(50) DEFAULT NULL, CHANGE adresse_occupant adresse_occupant VARCHAR(100) DEFAULT NULL, CHANGE cp_occupant cp_occupant VARCHAR(5) DEFAULT NULL, CHANGE ville_occupant ville_occupant VARCHAR(100) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE signalement DROP profile_declarant, DROP prenom_proprio, DROP code_postal_proprio, DROP ville_proprio, DROP tel_proprio_secondaire, DROP tel_declarant_secondaire, DROP civilite_occupant, CHANGE is_usager_abandon_procedure is_usager_abandon_procedure TINYINT(1) DEFAULT 0, CHANGE date_naissance_occupant date_naissance_occupant DATE DEFAULT NULL');
        $this->addSql('ALTER TABLE signalement DROP type_composition_logement');
        $this->addSql('ALTER TABLE signalement DROP situation_foyer');
        $this->addSql('ALTER TABLE signalement DROP information_procedure');
        $this->addSql('ALTER TABLE signalement DROP information_complementaire');
        $this->addSql('ALTER TABLE signalement CHANGE tel_proprio tel_proprio VARCHAR(15) DEFAULT NULL, CHANGE tel_proprio_secondaire tel_proprio_secondaire VARCHAR(15) DEFAULT NULL, CHANGE tel_declarant tel_declarant VARCHAR(15) DEFAULT NULL, CHANGE tel_declarant_secondaire tel_declarant_secondaire VARCHAR(15) DEFAULT NULL, CHANGE tel_occupant tel_occupant VARCHAR(15) DEFAULT NULL, CHANGE tel_occupant_bis tel_occupant_bis VARCHAR(15) DEFAULT NULL');
        $this->addSql('ALTER TABLE signalement CHANGE nom_occupant nom_occupant VARCHAR(50) NOT NULL, CHANGE prenom_occupant prenom_occupant VARCHAR(50) NOT NULL, CHANGE adresse_occupant adresse_occupant VARCHAR(100) NOT NULL, CHANGE cp_occupant cp_occupant VARCHAR(5) NOT NULL, CHANGE ville_occupant ville_occupant VARCHAR(100) NOT NULL');
    }
}
