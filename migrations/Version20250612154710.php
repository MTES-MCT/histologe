<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250612154710 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'migrate signalement data from getBailDpeInvariant to numeroInvariant';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE signalement ADD numero_invariant_rial VARCHAR(255) DEFAULT NULL');

        $where = 'WHERE JSON_VALUE(type_composition_logement, \'$.bail_dpe_invariant\') IS NOT NULL';
        $where .= ' AND (numero_invariant IS NULL OR numero_invariant = \'\')';
        $this->addSql('UPDATE signalement SET numero_invariant = JSON_VALUE(type_composition_logement, \'$.bail_dpe_invariant\') '.$where);
    }

    public function down(Schema $schema): void
    {
    }
}
