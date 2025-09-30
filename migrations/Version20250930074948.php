<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250930074948 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Update type column `reason` to longtext';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE email_delivery_issue CHANGE reason reason LONGTEXT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE email_delivery_issue CHANGE reason reason VARCHAR(255) DEFAULT NULL');
    }
}
