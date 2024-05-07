<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20240507143556 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'add manual address flag to signalement table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE signalement ADD manual_address_occupant TINYINT(1) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE signalement DROP manual_address_occupan');
    }
}
