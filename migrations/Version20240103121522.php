<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20240103121522 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Enable auto-affectation in Seine St-Denis';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(
            'UPDATE territory SET is_auto_affectation_enabled = 1 WHERE zip = \'93\''
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql(
            'UPDATE territory SET is_auto_affectation_enabled = 0 WHERE zip = \'93\''
        );
    }
}
