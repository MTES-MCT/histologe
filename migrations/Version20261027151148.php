<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261027151148 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Suppression des colonnes d\'adresse dépréciées de signalement (adresse, cp, ville, ban_id, insee, manual_address) et de leurs index';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('DROP INDEX idx_signalement_cp_occupant ON signalement');
        $this->addSql('DROP INDEX idx_signalement_same_address ON signalement');
        $this->addSql('ALTER TABLE signalement DROP adresse_occupant, DROP cp_occupant, DROP ville_occupant, DROP ban_id_occupant, DROP insee_occupant, DROP manual_address_occupant');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE signalement ADD adresse_occupant VARCHAR(100) DEFAULT NULL, ADD cp_occupant VARCHAR(5) DEFAULT NULL, ADD ville_occupant VARCHAR(100) DEFAULT NULL, ADD ban_id_occupant VARCHAR(50) DEFAULT NULL, ADD insee_occupant VARCHAR(10) DEFAULT NULL, ADD manual_address_occupant TINYINT DEFAULT NULL');
        $this->addSql('CREATE INDEX idx_signalement_cp_occupant ON signalement (cp_occupant)');
        $this->addSql('CREATE INDEX idx_signalement_same_address ON signalement (adresse_occupant, cp_occupant, ville_occupant)');
    }
}
