<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250324150108 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'add agence fields to signalement';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE signalement ADD nom_agence VARCHAR(255) DEFAULT NULL, ADD denomination_agence VARCHAR(255) DEFAULT NULL, ADD prenom_agence VARCHAR(255) DEFAULT NULL, ADD adresse_agence VARCHAR(255) DEFAULT NULL, ADD code_postal_agence VARCHAR(5) DEFAULT NULL, ADD ville_agence VARCHAR(255) DEFAULT NULL, ADD tel_agence VARCHAR(128) DEFAULT NULL, ADD tel_agence_secondaire VARCHAR(128) DEFAULT NULL, ADD mail_agence VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE signalement DROP nom_agence, DROP denomination_agence, DROP prenom_agence, DROP adresse_agence, DROP code_postal_agence, DROP ville_agence, DROP tel_agence, DROP tel_agence_secondaire, DROP mail_agence');
    }
}
