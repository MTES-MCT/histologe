<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250725121119 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'add description to notification';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE notification ADD description LONGTEXT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
    }
}
