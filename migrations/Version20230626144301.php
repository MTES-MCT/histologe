<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20230626144301 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'add activate account notification option for users';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user ADD is_activate_account_notification_enabled TINYINT(1) NOT NULL DEFAULT 1');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user DROP is_activate_account_notification_enabled');
    }
}
