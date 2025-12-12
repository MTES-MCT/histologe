<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251211114325 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Remove has_done_subscriptions_choice column from user table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user DROP has_done_subscriptions_choice');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user ADD has_done_subscriptions_choice TINYINT(1) NOT NULL');
    }
}
