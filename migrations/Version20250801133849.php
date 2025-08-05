<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250801133849 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add has_done_subscriptions_choice column to user table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user ADD has_done_subscriptions_choice TINYINT(1) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user DROP has_done_subscriptions_choice');
    }
}
