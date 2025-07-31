<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250731142328 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Update affectation status from CLOSED to FERME';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("UPDATE affectation SET statut = 'FERME' WHERE statut = 'CLOSED'");
    }

    public function down(Schema $schema): void
    {
    }
}
