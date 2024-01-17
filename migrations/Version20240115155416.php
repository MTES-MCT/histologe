<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20240115155416 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Delete dateEntreee on signalement if date signalement < date Entree';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('UPDATE signalement SET date_entree = null WHERE date_entree > created_at');
    }

    public function down(Schema $schema): void
    {
    }
}
