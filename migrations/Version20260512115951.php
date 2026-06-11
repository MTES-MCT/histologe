<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260512115951 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajout d\'un index sur les champs adresse_occupant, cp_occupant et ville_occupant de signalement pour améliorer les performances de la recherche de signalements à la même adresse';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE INDEX idx_signalement_same_address ON signalement (adresse_occupant, cp_occupant, ville_occupant)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX idx_signalement_same_address ON signalement');
    }
}
