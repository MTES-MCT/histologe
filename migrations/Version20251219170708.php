<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251219170708 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Change file description column type from TINYTEXT to VARCHAR(250)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE file MODIFY description VARCHAR(250) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE file MODIFY description TINYTEXT DEFAULT NULL');
    }
}
