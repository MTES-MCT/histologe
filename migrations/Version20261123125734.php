<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261123125734 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Supprime les interventions de type ARRETE_PREFECTORAL et la colonne additional_information';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("DELETE FROM intervention WHERE type = 'ARRETE_PREFECTORAL'");
        $this->addSql('ALTER TABLE intervention DROP additional_information');
    }

    public function down(Schema $schema): void
    {
        $this->throwIrreversibleMigrationException();
    }
}
