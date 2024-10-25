<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20241018093257 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'add column has_permission_affectation to User';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user ADD has_permission_affectation TINYINT(1) DEFAULT 0 NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user DROP has_permission_affectation');
    }
}
