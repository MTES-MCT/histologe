<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250502060650 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Esabora - Mise à jour de la colonne external_operator dans la table intervention';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("
            UPDATE intervention
            SET external_operator = done_by
            WHERE provider_name = 'esabora'
            AND type LIKE 'VISITE%'
            AND done_by NOT LIKE 'ARS'
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("
            UPDATE intervention
            SET external_operator = NULL
            WHERE provider_name = 'esabora'
            AND type LIKE 'VISITE%'
            AND done_by NOT LIKE 'ARS'
        ");
    }
}
