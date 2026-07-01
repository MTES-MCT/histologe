<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260701161103 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Drop boolean column mainlevee';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE arrete DROP main_levee');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE arrete ADD main_levee TINYINT(1) NOT NULL');
    }
}
