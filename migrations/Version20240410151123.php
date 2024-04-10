<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20240410151123 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Change description column formmat on file';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE file CHANGE description description TINYTEXT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE file CHANGE description description LONGTEXT DEFAULT NULL');
    }
}
