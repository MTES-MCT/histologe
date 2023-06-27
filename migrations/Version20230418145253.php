<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20230418145253 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'add context property to suivi';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE suivi ADD context VARCHAR(100) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE suivi DROP context');
    }
}
