<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261019153001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add category column to service_secours_route';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE service_secours_route ADD category VARCHAR(50) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE service_secours_route DROP category');
    }
}
