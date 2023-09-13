<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230913125858 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add fields to move signalement draft to signalement';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE signalement ADD profile_declarant VARCHAR(255) DEFAULT NULL, ADD prenom_proprio VARCHAR(255) DEFAULT NULL, ADD code_postal_proprio VARCHAR(5) DEFAULT NULL, ADD ville_proprio VARCHAR(255) DEFAULT NULL, ADD tel_proprio_bis VARCHAR(15) DEFAULT NULL, ADD tel_declarant_bis VARCHAR(15) DEFAULT NULL, ADD civiilite_occupant VARCHAR(10) DEFAULT NULL, ADD type_composition JSON DEFAULT NULL, ADD situation_foyer JSON DEFAULT NULL');
        $this->addSql('ALTER TABLE signalement ADD information_procedure JSON DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE signalement DROP profile_declarant, DROP prenom_proprio, DROP code_postal_proprio, DROP ville_proprio, DROP tel_proprio_bis, DROP tel_declarant_bis, DROP civiilite_occupant, DROP type_composition, DROP situation_foyer, CHANGE is_usager_abandon_procedure is_usager_abandon_procedure TINYINT(1) DEFAULT 0, CHANGE date_naissance_occupant date_naissance_occupant DATE DEFAULT NULL');
        $this->addSql('ALTER TABLE signalement DROP information_procedure');
    }
}
