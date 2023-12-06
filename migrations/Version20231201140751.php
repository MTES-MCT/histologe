<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20231201140751 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'add is_auto_affectation_enabled property to territory';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE territory ADD is_auto_affectation_enabled TINYINT(1) NOT NULL DEFAULT 0');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE territory DROP is_auto_affectation_enabled');
    }
}
